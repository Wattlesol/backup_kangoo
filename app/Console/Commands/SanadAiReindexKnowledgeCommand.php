<?php

namespace App\Console\Commands;

use App\Services\SanadVectorStoreService;
use Illuminate\Console\Command;

class SanadAiReindexKnowledgeCommand extends Command
{
    protected $signature = 'sanad:ai-reindex-knowledge';

    protected $description = 'Rebuild Sanad AI knowledge chunks and local vector embeddings.';

    public function handle(SanadVectorStoreService $vectorStore): int
    {
        $count = $vectorStore->reindexAll();
        $this->info("Reindexed {$count} Sanad AI knowledge item(s).");

        return self::SUCCESS;
    }
}
