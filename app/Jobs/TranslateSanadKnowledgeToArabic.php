<?php

namespace App\Jobs;

use App\Models\SanadAiKnowledgeItem;
use App\Services\SanadKnowledgeArabicTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateSanadKnowledgeToArabic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 2;

    public function __construct(public int $knowledgeItemId, public bool $force = false)
    {
    }

    public function handle(SanadKnowledgeArabicTranslationService $translator): void
    {
        $item = SanadAiKnowledgeItem::find($this->knowledgeItemId);
        if ($item) {
            $translator->translate($item, $this->force);
        }
    }
}
