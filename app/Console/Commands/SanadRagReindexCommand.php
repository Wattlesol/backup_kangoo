<?php

namespace App\Console\Commands;

use App\Services\SanadVectorStoreService;
use Illuminate\Console\Command;

class SanadRagReindexCommand extends Command
{
    protected $signature = 'sanad:rag-reindex';
    protected $description = 'Re-chunk and re-index all Sanad AI Knowledge Base items using structure-aware semantic vectors.';

    public function handle(SanadVectorStoreService $vectorStore): int
    {
        $this->info('Starting Sanad Knowledge Base re-indexing...');
        $count = $vectorStore->reindexAll();
        $this->info("Successfully re-indexed {$count} knowledge items with semantic structure-aware chunking.");

        return Command::SUCCESS;
    }
}
