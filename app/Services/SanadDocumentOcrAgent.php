<?php

namespace App\Services;

use App\Models\SanadDocumentVaultItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SanadDocumentOcrAgent
{
    public function __construct(
        private SanadNvidiaAiClient $ai,
        private SanadLangSmithTracer $tracer
    ) {
    }

    public function analyze(SanadDocumentVaultItem $document): array
    {
        $media = $document->getFirstMedia('sanad_document') ?: $document->getFirstMedia('document');
        $path = $media ? $media->getPath() : null;

        $document->forceFill([
            'ocr_status' => 'processing',
            'ocr_processed_at' => now(),
        ])->save();

        $analysis = $this->analyzeFile(
            $document->document_type,
            $document->file_name,
            $path,
            optional($media)->mime_type
        );
        $expiryDate = $this->normalizeDate($analysis['expiry_date'] ?? null);
        $reminderDate = $expiryDate ? $expiryDate->copy()->subMonthNoOverflow() : null;
        $confidence = $expiryDate ? (float) ($analysis['confidence'] ?? 0.75) : 0.0;
        $status = $expiryDate ? 'completed' : 'needs_review';

        $document->forceFill([
            'expiry_date' => optional($expiryDate)->toDateString(),
            'expiry_reminder_at' => optional($reminderDate)->toDateString(),
            'expiry_reminder_enabled' => $expiryDate ? $document->expiry_reminder_enabled !== false : $document->expiry_reminder_enabled,
            'ocr_status' => $status,
            'ocr_confidence' => $confidence,
            'ocr_metadata' => $analysis['metadata'],
            'ocr_processed_at' => now(),
        ])->save();

        return $analysis['metadata'];
    }

    public function analyzeFile(string $documentType, string $fileName, ?string $path, ?string $mimeType): array
    {
        $fallback = $this->fallbackResult($documentType, $fileName);
        $result = $fallback;
        $ocrModel = config('sanad.ai.ocr_model') ?: config('sanad.ai.model');
        $providerMetadata = [
            'agent' => 'sanad_document_ocr_agent',
            'chain' => 'langchain_ocr_expiry_extraction',
            'provider' => config('sanad.ai.provider'),
            'model' => $ocrModel,
            'media_mime_type' => $mimeType,
            'media_file_name' => $fileName,
        ];

        if ($path && is_file($path) && config('sanad.ai.enabled') && config('sanad.ai.api_key')) {
            $preparedImage = null;
            try {
                $messagePath = $path;
                $messageMimeType = $mimeType;
                if ($this->canSendAsImage($mimeType)) {
                    $preparedImage = $this->prepareImageForOcr($path);
                    if ($preparedImage) {
                        $messagePath = $preparedImage['path'];
                        $messageMimeType = $preparedImage['mime_type'];
                        $providerMetadata['prepared_image'] = [
                            'mime_type' => $messageMimeType,
                            'bytes' => filesize($messagePath) ?: null,
                            'width' => $preparedImage['width'],
                            'height' => $preparedImage['height'],
                        ];
                    }
                }

                $completion = $this->ai->chat($this->messages($documentType, $fileName, $messagePath, $messageMimeType), [
                    'model' => $ocrModel,
                    'fallback_models' => [],
                ] + $this->ocrModelOptions($ocrModel));
                $parsed = $this->parseResponse($completion['content']);
                if ($parsed) {
                    $result = array_merge($fallback, $parsed);
                }
                $providerMetadata['nvidia'] = [
                    'raw_id' => data_get($completion, 'raw.id'),
                    'finish_reason' => data_get($completion, 'raw.choices.0.finish_reason'),
                    'usage' => data_get($completion, 'raw.usage'),
                ];
            } catch (\Throwable $exception) {
                $providerMetadata['provider_error'] = $exception->getMessage();
            } finally {
                if ($preparedImage && !empty($preparedImage['path']) && is_file($preparedImage['path'])) {
                    @unlink($preparedImage['path']);
                }
            }
        }

        $expiryDate = $this->normalizeDate($result['expiry_date'] ?? null);
        $reminderDate = $expiryDate ? $expiryDate->copy()->subMonthNoOverflow() : null;
        $confidence = $expiryDate ? (float) ($result['confidence'] ?? 0.75) : 0.0;
        $status = $expiryDate ? 'completed' : 'needs_review';

        $traceId = $this->tracer->trace('sanad-document-ocr-agent', [
            'document_type' => $documentType,
            'file_name' => $fileName,
        ], [
            'expiry_date' => optional($expiryDate)->toDateString(),
            'reminder_date' => optional($reminderDate)->toDateString(),
            'confidence' => $confidence,
            'status' => $status,
        ], $providerMetadata, config('sanad.ai.langsmith.ocr_project', 'sanad-ocr'));

        $metadata = array_filter([
            'agent' => 'sanad_document_ocr_agent',
            'chain' => 'langchain_ocr_expiry_extraction',
            'document_kind' => $result['document_kind'] ?? null,
            'raw_expiry_text' => $result['raw_expiry_text'] ?? null,
            'reasoning' => $result['reasoning'] ?? null,
            'langsmith_run_id' => $traceId,
            'provider' => $providerMetadata,
        ]);

        return [
            'expiry_date' => optional($expiryDate)->toDateString(),
            'expiry_reminder_at' => optional($reminderDate)->toDateString(),
            'ocr_status' => $status,
            'ocr_confidence' => $confidence,
            'message' => $this->failureMessage($providerMetadata, $result),
            'metadata' => $metadata,
        ];
    }

    private function failureMessage(array $providerMetadata, array $result): ?string
    {
        if (empty($providerMetadata['provider_error']) && !empty($result['expiry_date'])) {
            return null;
        }

        if (!empty($providerMetadata['provider_error'])) {
            return 'Quick AI could not read this file with the configured OCR model. Please set a manual reminder or try a clearer image.';
        }

        return 'Quick AI reviewed the document but could not find a visible expiry date. Please set a manual follow-up reminder.';
    }

    private function ocrModelOptions(?string $model): array
    {
        if ($model === 'meta/muse-glimmer-30b') {
            return [
                'temperature' => 0.95,
                'top_p' => 1,
                'reasoning_effort' => 'low',
                'max_tokens' => 900,
            ];
        }

        return [
            'temperature' => 0,
            'max_tokens' => 700,
        ];
    }

    private function prepareImageForOcr(string $path): ?array
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $imageInfo = @getimagesize($path);
        if (!$imageInfo) {
            return null;
        }

        [$width, $height] = $imageInfo;
        $source = @imagecreatefromstring(file_get_contents($path));
        if (!$source) {
            return null;
        }

        $maxSide = 1400;
        $scale = min(1, $maxSide / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $directory = storage_path('app/sanad-ocr-prepared');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $preparedPath = $directory . '/' . (string) Str::uuid() . '.jpg';
        imagejpeg($target, $preparedPath, 78);
        imagedestroy($source);
        imagedestroy($target);

        return [
            'path' => $preparedPath,
            'mime_type' => 'image/jpeg',
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }

    private function messages(string $documentType, string $fileName, string $path, ?string $mimeType): array
    {
        $content = [
            [
                'type' => 'text',
                'text' => "You are a separate OCR extraction agent for Sanad Document Vault. Extract only the document identity and expiry date from the attached document image. This is not a chat task.\n\nReturn strict JSON only with keys: document_kind, expiry_date, raw_expiry_text, confidence, reasoning.\n- expiry_date must be ISO YYYY-MM-DD or null.\n- confidence must be 0 to 1.\n- For licenses, IDs, passports, visas, Iqama, commercial registers, hunt for expiry/valid until/date of expiry fields.\n- Do not invent a date. If unclear, expiry_date null.\n\nDocument label supplied by user: {$documentType}\nFile name: {$fileName}",
            ],
        ];

        if ($this->canSendAsImage($mimeType)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($path)),
                ],
            ];
        } else {
            $content[0]['text'] .= "\n\nThe file type is not directly viewable by this OCR agent. Use the label and filename only, and return null if an expiry date is not clearly present.";
        }

        return [
            ['role' => 'user', 'content' => $content],
        ];
    }

    private function parseResponse(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(.*?)```/is', $content, $match)) {
            $content = trim($match[1]);
        } elseif (preg_match('/\{.*\}/s', $content, $match)) {
            $content = $match[0];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function fallbackResult(string $documentType, string $fileName): array
    {
        $haystack = trim($documentType . ' ' . $fileName);
        $date = $this->extractDateFromText($haystack);

        return [
            'document_kind' => $documentType,
            'expiry_date' => $date,
            'raw_expiry_text' => $date,
            'confidence' => $date ? 0.35 : 0,
            'reasoning' => $date ? 'Detected a possible date from the file name or document label.' : 'No clear expiry date detected without OCR.',
        ];
    }

    private function extractDateFromText(string $text): ?string
    {
        $patterns = [
            '/\b(20\d{2})[-\/.](0?[1-9]|1[0-2])[-\/.](0?[1-9]|[12]\d|3[01])\b/',
            '/\b(0?[1-9]|[12]\d|3[01])[-\/.](0?[1-9]|1[0-2])[-\/.](20\d{2})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                if (strlen($match[1]) === 4) {
                    return Carbon::createFromDate((int) $match[1], (int) $match[2], (int) $match[3])->toDateString();
                }

                return Carbon::createFromDate((int) $match[3], (int) $match[2], (int) $match[1])->toDateString();
            }
        }

        return null;
    }

    private function normalizeDate(?string $value): ?Carbon
    {
        if (!$value || Str::lower($value) === 'null') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function canSendAsImage(?string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
    }
}
