<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class SanadKnowledgeIngestionService
{
    public function extract(?string $manualContent, array $pdfs = [], ?string $googleDocUrl = null): array
    {
        $parts = [];
        $metadata = [];

        if (trim((string) $manualContent) !== '') {
            $parts[] = trim($manualContent);
        }

        foreach ($pdfs as $pdf) {
            if (!$pdf instanceof UploadedFile) {
                continue;
            }

            $path = $pdf->store('sanad-ai/knowledge-pdfs');
            $metadata['uploaded_pdfs'][] = [
                'file_name' => $pdf->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $pdf->getMimeType(),
                'size' => $pdf->getSize(),
            ];

            $parts[] = $this->extractPdfText(Storage::path($path));
        }

        if ($googleDocUrl) {
            $metadata['google_doc_url'] = $googleDocUrl;
            $parts[] = $this->extractGoogleDocText($googleDocUrl);
        }

        $content = collect($parts)
            ->map(fn ($part) => trim(preg_replace('/\s+/', ' ', (string) $part)))
            ->filter()
            ->implode("\n\n");

        return [
            'content' => $content,
            'metadata' => $metadata,
        ];
    }

    private function extractPdfText(string $path): string
    {
        try {
            return trim((new Parser())->parseFile($path)->getText());
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractGoogleDocText(string $url): string
    {
        $documentId = $this->googleDocumentId($url);
        $exportUrl = $documentId
            ? "https://docs.google.com/document/d/{$documentId}/export?format=txt"
            : $url;

        try {
            $response = Http::timeout(20)->get($exportUrl);
            if (!$response->successful()) {
                return '';
            }

            return trim(strip_tags($response->body()));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function googleDocumentId(string $url): ?string
    {
        if (preg_match('#/document/d/([^/]+)#', $url, $matches)) {
            return $matches[1];
        }

        return Str::contains($url, 'docs.google.com') ? null : null;
    }
}
