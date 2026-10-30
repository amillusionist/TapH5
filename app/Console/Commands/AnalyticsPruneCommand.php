<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AnalyticsPruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:prune
                            {--days=90 : Retention days before raw events are aggregated and pruned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate older raw analytics events into daily rollups and prune raw records';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsService $analyticsService): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 90;
        }

        $cutoffDate = Carbon::now()->subDays($days)->toDateString();

        $this->info('=============================================');
        $this->info('      TapH5 Analytics Retention Pruner       ');
        $this->info('=============================================');
        $this->info("Retention Period : {$days} days");
        $this->info("Cutoff Date      : {$cutoffDate}");

        $result = $analyticsService->aggregateAndPrune($days);

        $this->line('');
        $this->info("Successfully aggregated {$result['aggregated_days']} daily date-game groupings.");
        $this->info("Pruned {$result['deleted_events']} historical raw events.");
        $this->line('');

        return self::SUCCESS;
    }
}
