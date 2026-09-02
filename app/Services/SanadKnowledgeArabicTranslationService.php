<?php

namespace App\Services;

use App\Models\SanadAiKnowledgeItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SanadKnowledgeArabicTranslationService
{
    public function __construct(
        private SanadNvidiaAiClient $ai,
        private SanadVectorStoreService $vectorStore
    ) {
    }

    public function bilingualPair(string $title, string $content): array
    {
        $title = trim($title);
        $content = trim($content);
        if ($title === '' || $content === '') {
            throw new \InvalidArgumentException('A title and knowledge content are required before translation.');
        }

        $contentLanguage = $this->detectLanguage($content);
        $titleLanguage = $this->detectLanguage($title);
        $pair = [
            'source_language' => $contentLanguage,
            'title_en' => $titleLanguage === 'en' ? $title : $this->translateText($title, 'English'),
            'title_ar' => $titleLanguage === 'ar' ? $title : $this->translateText($title, 'Modern Standard Arabic'),
            'content_en' => $contentLanguage === 'en' ? $content : $this->translateFullContent($content, 'English'),
            'content_ar' => $contentLanguage === 'ar' ? $content : $this->translateFullContent($content, 'Modern Standard Arabic'),
        ];

        foreach (['title_en', 'title_ar', 'content_en', 'content_ar'] as $field) {
            if (trim((string) $pair[$field]) === '') {
                throw new \RuntimeException("Translation failed: {$field} is empty. Nothing was saved.");
            }
        }
        if ($this->detectLanguage($pair['title_ar']) !== 'ar' || $this->detectLanguage($pair['content_ar']) !== 'ar') {
            throw new \RuntimeException('Translation failed: the Arabic title or content is not valid. Nothing was saved.');
        }
        if ($this->containsUntranslatedLatin($pair['title_ar']) || $this->containsUntranslatedLatin($pair['content_ar'])) {
            throw new \RuntimeException('Translation failed: untranslated English text remains in the Arabic version. Nothing was saved.');
        }
        if ($this->detectLanguage($pair['title_en']) !== 'en' || $this->detectLanguage($pair['content_en']) !== 'en') {
            throw new \RuntimeException('Translation failed: the English title or content is not valid. Nothing was saved.');
        }

        return $pair;
    }

    public function bilingualText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new \InvalidArgumentException('Text is required before translation.');
        }

        $sourceLanguage = $this->detectLanguage($text);
        $pair = [
            'source_language' => $sourceLanguage,
            'text_en' => $sourceLanguage === 'en' ? $text : $this->translateText($text, 'English'),
            'text_ar' => $sourceLanguage === 'ar' ? $text : $this->translateText($text, 'Modern Standard Arabic'),
        ];

        if (trim($pair['text_en']) === '' || trim($pair['text_ar']) === '') {
            throw new \RuntimeException('Translation failed: both English and Arabic versions are required. Nothing was saved.');
        }
        if ($this->detectLanguage($pair['text_en']) !== 'en') {
            throw new \RuntimeException('Translation failed: the English version is not valid. Nothing was saved.');
        }
        if ($this->detectLanguage($pair['text_ar']) !== 'ar' || $this->containsUntranslatedLatin($pair['text_ar'])) {
            throw new \RuntimeException('Translation failed: the Arabic version is not valid. Nothing was saved.');
        }
        if (mb_strlen($pair['text_en']) > 100 || mb_strlen($pair['text_ar']) > 100) {
            throw new \RuntimeException('Translation failed: the translated document name exceeds 100 characters. Nothing was saved.');
        }

        return $pair;
    }

    public function translate(SanadAiKnowledgeItem $item, bool $force = false): SanadAiKnowledgeItem
    {
        if (!$force && $item->title_ar && $item->content_ar && $item->title && $item->content) {
            return $item;
        }

        $sourceTitle = $item->title_ar ?: $item->title;
        $sourceContent = $item->content_ar ?: $item->content;
        $pair = $this->bilingualPair($sourceTitle, $sourceContent);

        $item->update([
            'title' => $pair['title_en'],
            'title_ar' => $pair['title_ar'],
            'category_ar' => $this->arabicCategory($item->category),
            'content' => $pair['content_en'],
            'content_ar' => $pair['content_ar'],
            'metadata' => $this->translationMetadata($item->metadata ?: [], $pair, $sourceContent),
        ]);

        $this->vectorStore->indexKnowledgeItem($item->fresh());

        return $item->fresh();
    }

    public function translationMetadata(array $metadata, array $pair, string $sourceContent): array
    {
        $metadata['bilingual_translation'] = [
            'status' => 'completed',
            'source_language' => $pair['source_language'],
            'translated_at' => now()->toIso8601String(),
            'source_characters' => mb_strlen($sourceContent),
            'mode' => 'full_translation',
        ];

        return $metadata;
    }

    public function arabicCategory(?string $category): string
    {
        return match (Str::lower((string) $category)) {
            'documents' => 'المستندات',
            'support' => 'الدعم',
            'payments' => 'المدفوعات',
            'sla' => 'اتفاقية مستوى الخدمة',
            default => 'عام',
        };
    }

    private function translateFullContent(string $content, string $targetLanguage): string
    {
        $translated = [];
        foreach ($this->splitForTranslation($content) as $index => $chunk) {
            $result = $this->translateText($chunk, $targetLanguage);
            if ($result === '') {
                throw new \RuntimeException('Translation failed on content part ' . ($index + 1) . '. Nothing was saved.');
            }
            $translated[] = $result;
        }

        return trim(implode("\n\n", $translated));
    }

    private function translateText(string $text, string $targetLanguage): string
    {
        $arabicRule = $targetLanguage === 'Modern Standard Arabic'
            ? ' Use Arabic script for every translatable word and transliterate names when appropriate. Do not leave English words, mention the source tags, or add English commentary; URLs may remain unchanged.'
            : '';
        $prompt = "Translate the exact text enclosed in <source> tags into {$targetLanguage}. The source may be a short title or a long document. Preserve all facts, names, numbers, URLs, headings, bullets, requirements, and line structure. Do not summarize, omit, explain, refuse, censor, or add anything. Return only the translation, without labels or commentary.{$arabicRule}\n\n<source>\n{$text}\n</source>";

        try {
            if (config('sanad.ai.translation_ollama_only')) {
                return $this->translateWithOllama($prompt);
            }

            $completion = $this->ai->chat([[
                'role' => 'user',
                'content' => $prompt,
            ]], [
                'temperature' => 0,
                'max_tokens' => 4096,
                'fallback_models' => [],
                'parse_response' => false,
            ]);
            $result = trim((string) ($completion['content'] ?? ''));
            if ($result === '') {
                throw new \RuntimeException('The translation provider returned an empty response.');
            }

            return $result;
        } catch (\Throwable $e) {
            if (!config('sanad.ai.translation_ollama_fallback')) {
                throw $e;
            }

            return $this->translateWithOllama($prompt);
        }
    }

    private function translateWithOllama(string $prompt): string
    {
        $response = Http::timeout(300)->post(rtrim(config('sanad.ai.translation_ollama_url'), '/') . '/api/generate', [
            'model' => config('sanad.ai.translation_ollama_model'),
            'prompt' => $prompt,
            'stream' => false,
            'options' => ['temperature' => 0, 'num_predict' => 4096],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('The translation provider failed. Nothing was saved.');
        }

        $result = trim((string) data_get($response->json(), 'response', ''));
        if ($result === '') {
            throw new \RuntimeException('The translation provider returned an empty response. Nothing was saved.');
        }

        return $result;
    }

    private function splitForTranslation(string $content, int $maxCharacters = 3500): array
    {
        if (mb_strlen($content) <= $maxCharacters) {
            return [$content];
        }

        $parts = preg_split('/(\n{2,})/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$content];
        $chunks = [];
        $current = '';
        foreach ($parts as $part) {
            while (mb_strlen($part) > $maxCharacters) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }
                $chunks[] = mb_substr($part, 0, $maxCharacters);
                $part = mb_substr($part, $maxCharacters);
            }
            if (mb_strlen($current . $part) > $maxCharacters && $current !== '') {
                $chunks[] = $current;
                $current = $part;
            } else {
                $current .= $part;
            }
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter($chunks, fn ($chunk) => trim($chunk) !== ''));
    }

    private function detectLanguage(string $text): string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $text) ?: '';
        if ($letters === '') {
            return app()->getLocale() === 'ar' ? 'ar' : 'en';
        }
        preg_match_all('/\p{Arabic}/u', $letters, $matches);

        return count($matches[0]) / max(1, mb_strlen($letters)) >= 0.35 ? 'ar' : 'en';
    }

    private function containsUntranslatedLatin(string $text): bool
    {
        $withoutUrls = preg_replace('/https?:\/\/\S+|www\.\S+|\S+@\S+/iu', '', $text) ?: $text;

        return (bool) preg_match('/[A-Za-z]{2,}/', $withoutUrls);
    }
}
