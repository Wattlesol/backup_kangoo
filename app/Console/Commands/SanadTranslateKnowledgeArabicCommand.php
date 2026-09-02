<?php

namespace App\Console\Commands;

use App\Models\SanadAiKnowledgeItem;
use App\Services\SanadKnowledgeArabicTranslationService;
use Illuminate\Console\Command;

class SanadTranslateKnowledgeArabicCommand extends Command
{
    protected $signature = 'sanad:translate-knowledge-ar {--id=} {--force}';
    protected $description = 'Create and index the parallel Arabic Sanad knowledge base';

    public function handle(SanadKnowledgeArabicTranslationService $translator): int
    {
        $query = SanadAiKnowledgeItem::where('is_active', true);
        if ($this->option('id')) {
            $query->whereKey((int) $this->option('id'));
        }
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('title_ar')->orWhereNull('content_ar');
            });
        }

        $items = $query->get();
        foreach ($items as $item) {
            $this->line("Translating #{$item->id}: {$item->title}");
            try {
                $translator->translate($item, (bool) $this->option('force'));
                $this->info("Translated #{$item->id}");
            } catch (\Throwable $e) {
                $this->error("Failed #{$item->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
