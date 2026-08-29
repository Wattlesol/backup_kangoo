<?php

namespace App\Services;

use App\Models\SanadAiKnowledgeChunk;
use App\Models\SanadAiKnowledgeItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SanadVectorStoreService
{
    public function __construct(private SanadNvidiaAiClient $ai)
    {
    }

    public function indexKnowledgeItem(SanadAiKnowledgeItem $item): void
    {
        $this->deleteKnowledgeItemVectors($item);
        $item->chunks()->delete();

        $localizedContent = ['en' => $item->content];
        if (trim((string) $item->content_ar) !== '') {
            $localizedContent['ar'] = $item->content_ar;
        }

        $chunkIndex = 0;
        foreach ($localizedContent as $language => $documentContent) {
            foreach ($this->chunk($documentContent) as $content) {
                $embedding = $this->ai->embed($content, 'passage') ?: $this->localEmbedding($content);
                $chunk = SanadAiKnowledgeChunk::create([
                    'knowledge_item_id' => $item->id,
                    'chunk_index' => $chunkIndex,
                    'content' => $content,
                    'embedding' => $embedding,
                    'embedding_model' => config('sanad.ai.embedding_model') ?: 'local-hash',
                    'vector_id' => 'sanad-knowledge-' . $item->id . '-' . $language . '-' . $chunkIndex,
                    'metadata' => [
                        'language' => $language,
                        'title' => $language === 'ar' ? ($item->title_ar ?: $item->title) : $item->title,
                        'category' => $language === 'ar' ? ($item->category_ar ?: $item->category) : $item->category,
                        'visible_to' => $item->visible_to,
                        'source_url' => data_get($item->metadata, 'source_url'),
                    ],
                ]);

                $this->upsertChroma($chunk, $item);
                $chunkIndex++;
            }
        }
    }

    public function deleteKnowledgeItemVectors(SanadAiKnowledgeItem $item): void
    {
        $vectorIds = $item->chunks()
            ->pluck('vector_id')
            ->filter()
            ->values()
            ->all();

        if (empty($vectorIds) || config('sanad.ai.vector_store') !== 'chroma') {
            return;
        }

        try {
            $collectionId = $this->chromaCollectionId();
            if (!$collectionId) {
                return;
            }

            Http::timeout(10)->post($this->chromaUrl('/api/v1/collections/' . $collectionId . '/delete'), [
                'ids' => $vectorIds,
            ]);
        } catch (\Throwable $e) {
            //
        }
    }

    public function search(string $question, string $audience = 'customer', int $limit = 5): Collection
    {
        $queryEmbedding = $this->ai->embed($question, 'query') ?: $this->localEmbedding($question);
        $chromaMatches = $this->searchChroma($queryEmbedding, $audience, 15);

        $dbChunks = SanadAiKnowledgeChunk::with('knowledgeItem')
            ->whereHas('knowledgeItem', function ($query) use ($audience) {
                $query->where('is_active', true)
                    ->where(function ($visibilityQuery) use ($audience) {
                        $visibilityQuery->whereNull('visible_to')
                            ->orWhereJsonContains('visible_to', $audience)
                            ->orWhereJsonContains('visible_to', 'user');
                    });
            })
            ->get();

        $candidates = collect();

        foreach ($dbChunks as $chunk) {
            $item = $chunk->knowledgeItem;
            if (!$item || !$item->is_active) {
                continue;
            }

            $chromaMatch = $chromaMatches->first(fn ($m) => data_get($m, 'chunk.id') === $chunk->id);
            $vectorScore = $chromaMatch ? (float) $chromaMatch['score'] : $this->cosine($queryEmbedding, $chunk->embedding ?: []);
            $keywordScores = $this->computeKeywordScore($question, $item, $chunk);

            $hybridScore = round((0.50 * $vectorScore) + (0.30 * $keywordScores['content']) + (0.20 * $keywordScores['title']), 4);

            $candidates->push([
                'chunk' => $chunk,
                'item' => $item,
                'score' => $hybridScore,
                'vector_score' => $vectorScore,
                'keyword_score' => $keywordScores['content'],
            ]);
        }

        return $candidates
            ->filter(fn ($match) => $match['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function computeKeywordScore(string $question, SanadAiKnowledgeItem $item, SanadAiKnowledgeChunk $chunk): array
    {
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'to', 'in', 'for', 'how', 'what', 'can', 'you', 'me', 'tell', 'take', 'it', 'be', 'this', 'that'];
        $rawTerms = preg_split('/[^\pL\pN]+/u', Str::lower($question));
        $terms = collect($rawTerms)->filter(fn ($t) => mb_strlen($t) >= 3 && !in_array($t, $stopWords, true))->values();

        if ($terms->isEmpty()) {
            return ['content' => 0.0, 'title' => 0.0];
        }

        $titleHaystack = Str::lower(implode(' ', array_filter([$item->title, $item->title_ar, $item->category, $item->category_ar])));
        $contentHaystack = Str::lower(implode(' ', array_filter([$item->title, $item->title_ar, $item->content, $item->content_ar, $chunk->content])));

        $titleMatches = 0;
        $contentMatches = 0;

        foreach ($terms as $term) {
            if (Str::contains($titleHaystack, $term)) {
                $titleMatches++;
            }
            if (Str::contains($contentHaystack, $term)) {
                $contentMatches++;
            }
        }

        return [
            'content' => min(1.0, $contentMatches / $terms->count()),
            'title' => min(1.0, $titleMatches / $terms->count()),
        ];
    }

    public function reindexAll(): int
    {
        $count = 0;
        $this->resetChromaCollection();
        SanadAiKnowledgeChunk::query()->delete();

        SanadAiKnowledgeItem::where('is_active', true)->chunkById(50, function ($items) use (&$count) {
            foreach ($items as $item) {
                $this->indexKnowledgeItem($item);
                $count++;
            }
        });

        return $count;
    }

    private function chunk(string $content): array
    {
        $cleaned = strip_tags($content);
        $cleaned = preg_replace('/\[×\]\(javascript:.*?\)/i', '', $cleaned);
        $cleaned = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $cleaned);
        $cleaned = preg_replace('/Source:\s*https?:\/\/\S+/i', '', $cleaned);
        $cleaned = preg_replace('/https?:\/\/\S+/', '', $cleaned);

        // Split text by markdown headings (# Heading) or double newlines (\n\n)
        $sections = preg_split('/(?=(?:^|\n)\s*#{1,4}\s+)/u', $cleaned);
        $chunks = [];
        $maxChunkSize = max(400, (int) config('sanad.ai.chunk_size', 800));

        foreach ($sections as $section) {
            $section = trim($section);
            if ($section === '') {
                continue;
            }

            // Extract heading if present
            $currentHeader = '';
            if (preg_match('/^(#{1,4}\s+.*)$/m', $section, $hMatch)) {
                $currentHeader = trim(preg_replace('/^#+\s*/', '', $hMatch[1]));
            }

            // Split section by paragraphs
            $paragraphs = preg_split('/\n{2,}/u', $section);
            $currentChunk = '';

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim(preg_replace('/\s+/', ' ', $paragraph));
                if ($paragraph === '') {
                    continue;
                }

                if (mb_strlen($currentChunk . ' ' . $paragraph) > $maxChunkSize && mb_strlen($currentChunk) > 100) {
                    $finalChunk = trim($currentChunk);
                    if ($currentHeader && !Str::contains($finalChunk, $currentHeader)) {
                        $finalChunk = "[$currentHeader] " . $finalChunk;
                    }
                    $chunks[] = $finalChunk;
                    $currentChunk = $paragraph;
                } else {
                    $currentChunk = $currentChunk === '' ? $paragraph : $currentChunk . ' ' . $paragraph;
                }
            }

            if (trim($currentChunk) !== '') {
                $finalChunk = trim($currentChunk);
                if ($currentHeader && !Str::contains($finalChunk, $currentHeader)) {
                    $finalChunk = "[$currentHeader] " . $finalChunk;
                }
                $chunks[] = $finalChunk;
            }
        }

        return $chunks ?: [trim(preg_replace('/\s+/', ' ', $cleaned))];
    }

    private function localEmbedding(string $text, int $dimensions = 256): array
    {
        $vector = array_fill(0, $dimensions, 0.0);
        $terms = preg_split('/[^\pL\pN]+/u', Str::lower($text));

        foreach ($terms as $term) {
            if (mb_strlen($term) < 3) {
                continue;
            }
            $index = abs(crc32($term)) % $dimensions;
            $vector[$index] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn ($value) => $value * $value, $vector))) ?: 1.0;

        return array_map(fn ($value) => round($value / $norm, 6), $vector);
    }

    private function cosine(array $a, array $b): float
    {
        $limit = min(count($a), count($b));
        if ($limit === 0) {
            return 0.0;
        }

        $dot = $normA = $normB = 0.0;
        for ($i = 0; $i < $limit; $i++) {
            $dot += (float) $a[$i] * (float) $b[$i];
            $normA += (float) $a[$i] * (float) $a[$i];
            $normB += (float) $b[$i] * (float) $b[$i];
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return round($dot / (sqrt($normA) * sqrt($normB)), 4);
    }

    private function upsertChroma(SanadAiKnowledgeChunk $chunk, SanadAiKnowledgeItem $item): void
    {
        if (config('sanad.ai.vector_store') !== 'chroma') {
            return;
        }

        try {
            $collectionId = $this->chromaCollectionId();
            if (!$collectionId) {
                return;
            }

            $response = Http::timeout(10)->post($this->chromaUrl('/api/v1/collections/' . $collectionId . '/upsert'), [
                'ids' => [$chunk->vector_id],
                'documents' => [$chunk->content],
                'embeddings' => [$chunk->embedding],
                'metadatas' => [[
                    'knowledge_item_id' => $item->id,
                    'chunk_id' => $chunk->id,
                    'title' => $item->title,
                    'category' => $item->category ?: 'General',
                    'language' => data_get($chunk->metadata, 'language', 'en'),
                    'visible_to' => implode(',', $item->visible_to ?: []),
                ]],
            ]);

            if (!$response->successful()) {
                $chunk->metadata = array_merge($chunk->metadata ?: [], [
                    'chroma_error' => $response->body(),
                ]);
                $chunk->save();
            }
        } catch (\Throwable $e) {
            $chunk->metadata = array_merge($chunk->metadata ?: [], [
                'chroma_error' => $e->getMessage(),
            ]);
            $chunk->save();
        }
    }

    private function searchChroma(array $embedding, string $audience, int $limit): Collection
    {
        if (config('sanad.ai.vector_store') !== 'chroma') {
            return collect();
        }

        try {
            $collectionId = $this->chromaCollectionId();
            if (!$collectionId) {
                return collect();
            }

            $response = Http::timeout(10)->post($this->chromaUrl('/api/v1/collections/' . $collectionId . '/query'), [
                'query_embeddings' => [$embedding],
                'n_results' => $limit,
                'include' => ['metadatas', 'documents', 'distances'],
            ]);

            if (!$response->successful()) {
                return collect();
            }

            $ids = collect(data_get($response->json(), 'metadatas.0', []))
                ->filter(function ($metadata) use ($audience) {
                    $visibleTo = array_filter(explode(',', (string) data_get($metadata, 'visible_to')));
                    return empty($visibleTo) || in_array($audience, $visibleTo, true) || in_array('user', $visibleTo, true);
                })
                ->pluck('chunk_id')
                ->filter()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            $chunks = SanadAiKnowledgeChunk::with('knowledgeItem')->whereIn('id', $ids)->get()->keyBy('id');
            $distances = collect(data_get($response->json(), 'distances.0', []));

            return $ids->map(function ($id, $index) use ($chunks, $distances) {
                $chunk = $chunks->get($id);
                if (!$chunk || !$chunk->knowledgeItem) {
                    return null;
                }

                return [
                    'chunk' => $chunk,
                    'item' => $chunk->knowledgeItem,
                    'score' => round(1 / (1 + (float) $distances->get($index, 0)), 4),
                ];
            })->filter()->values();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function resetChromaCollection(): void
    {
        if (config('sanad.ai.vector_store') !== 'chroma') {
            return;
        }

        try {
            $collection = $this->findChromaCollection();
            if ($collection) {
                Http::timeout(10)->delete($this->chromaUrl('/api/v1/collections/' . config('sanad.ai.chroma_collection')));
            }
            $this->chromaCollectionId(true);
        } catch (\Throwable $e) {
            //
        }
    }

    private function chromaCollectionId(bool $refresh = false): ?string
    {
        static $collectionId = null;

        if ($collectionId && !$refresh) {
            return $collectionId;
        }

        $collection = $this->findChromaCollection();
        if (!$collection) {
            $response = Http::timeout(10)->post($this->chromaUrl('/api/v1/collections'), [
                'name' => config('sanad.ai.chroma_collection'),
            ]);
            $collection = $response->successful() ? $response->json() : null;
        }

        $collectionId = data_get($collection, 'id');

        return $collectionId;
    }

    private function findChromaCollection(): ?array
    {
        $response = Http::timeout(10)->get($this->chromaUrl('/api/v1/collections'));
        if (!$response->successful()) {
            return null;
        }

        return collect($response->json())
            ->firstWhere('name', config('sanad.ai.chroma_collection'));
    }

    private function chromaUrl(string $path): string
    {
        return rtrim((string) config('sanad.ai.chroma_url'), '/') . '/' . ltrim($path, '/');
    }
}
