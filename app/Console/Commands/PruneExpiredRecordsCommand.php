<?php

namespace App\Console\Commands;

use App\Models\SurveyRequest;
use Illuminate\Console\Command;

class PruneExpiredRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surveys:prune-expired {--days=30 : The number of days before records are pruned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically delete non-feasible, rejected, or cancelled surveys and rejected/terminated links older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Pruning expired surveys and links older than {$days} days...");

        $results = SurveyRequest::pruneExpiredRecords($days);

        $this->info("Deleted surveys: {$results['deleted_surveys']}");
        $this->info("Deleted links: {$results['deleted_links']}");
        $this->info("Deleted service requests: {$results['deleted_service_requests']}");

        return Command::SUCCESS;
    }
}
