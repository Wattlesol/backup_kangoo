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
        $item->chunks()->delete();

        foreach ($this->chunk($item->content) as $index => $content) {
            $embedding = $this->ai->embed($content) ?: $this->localEmbedding($content);
            $chunk = SanadAiKnowledgeChunk::create([
                'knowledge_item_id' => $item->id,
                'chunk_index' => $index,
                'content' => $content,
                'embedding' => $embedding,
                'embedding_model' => config('sanad.ai.embedding_model') ?: 'local-hash',
                'vector_id' => 'sanad-knowledge-' . $item->id . '-' . $index,
                'metadata' => [
                    'title' => $item->title,
                    'category' => $item->category,
                    'visible_to' => $item->visible_to,
                    'source_url' => data_get($item->metadata, 'source_url'),
                ],
            ]);

            $this->upsertChroma($chunk, $item);
        }
    }

    public function search(string $question, string $audience = 'customer', int $limit = 5): Collection
    {
        $queryEmbedding = $this->ai->embed($question) ?: $this->localEmbedding($question);
        $chromaMatches = $this->searchChroma($queryEmbedding, $audience, $limit);
        if ($chromaMatches->isNotEmpty()) {
            return $chromaMatches;
        }

        $chunks = SanadAiKnowledgeChunk::with('knowledgeItem')
            ->whereHas('knowledgeItem', function ($query) use ($audience) {
                $query->where('is_active', true)
                    ->where(function ($visibilityQuery) use ($audience) {
                        $visibilityQuery->whereNull('visible_to')
                            ->orWhereJsonContains('visible_to', $audience)
                            ->orWhereJsonContains('visible_to', 'user');
                    });
            })
            ->get();

        return $chunks
            ->map(fn ($chunk) => [
                'chunk' => $chunk,
                'item' => $chunk->knowledgeItem,
                'score' => $this->cosine($queryEmbedding, $chunk->embedding ?: []),
            ])
            ->filter(fn ($match) => $match['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
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
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
        $size = max(300, (int) config('sanad.ai.chunk_size', 900));
        $overlap = max(0, min((int) config('sanad.ai.chunk_overlap', 120), $size - 1));
        $chunks = [];

        for ($offset = 0; $offset < mb_strlen($text); $offset += $size - $overlap) {
            $chunk = trim(mb_substr($text, $offset, $size));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks ?: [$text];
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
