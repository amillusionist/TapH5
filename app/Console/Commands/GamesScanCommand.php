<?php

namespace App\Console\Commands;

use App\Services\Games\GameScannerService;
use Illuminate\Console\Command;

class GamesScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:scan
                            {--game= : Optional specific game folder name to scan}
                            {--dry-run : Simulate discovery without writing changes to the database}
                            {--force : Force execution even if background scan flags exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan staging directory and automatically discover HTML5 games into draft records';

    /**
     * Execute the console command.
     */
    public function handle(GameScannerService $scannerService): int
    {
        $this->info('=============================================');
        $this->info('         TapH5 Game Discovery Scanner        ');
        $this->info('=============================================');

        $targetGame = $this->option('game');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No database records or thumbnails will be persisted.');
        }

        $result = $scannerService->scan($targetGame, $dryRun);

        if (! $result['success']) {
            $this->error('Error: '.$result['message']);

            return self::FAILURE;
        }

        $this->line("<comment>Staging Directory:</comment> {$result['staging_root']}");
        $this->line("<comment>Directories Found:</comment> {$result['discovered']}");
        $this->newLine();

        if (empty($result['results'])) {
            $this->info('No game directories found in staging.');
            $this->newLine();

            return self::SUCCESS;
        }

        foreach ($result['results'] as $index => $item) {
            $num = $index + 1;
            $title = $item['title'] ?? $item['folder'];
            $status = strtoupper((string) ($item['status'] ?? 'UNKNOWN'));

            if ($item['status'] === 'rejected') {
                $this->error("[{$num}] {$item['folder']} — REJECTED");
                $this->line("    <fg=red>Reason:</> {$item['reason']}");
            } elseif ($item['status'] === 'duplicate') {
                $this->warn("[{$num}] {$title} — DUPLICATE");
                $this->line("    <fg=yellow>Reason:</> {$item['reason']}");
                if (! empty($item['existing_game_id'])) {
                    $this->line("    <fg=gray>Existing Game ID:</> #{$item['existing_game_id']}");
                }
            } else {
                $this->info("[{$num}] {$title} ({$item['folder']}) — {$status}");
                $this->line("    <fg=gray>Entry File:</> {$item['entry_file']}");
                $this->line("    <fg=gray>Game Type:</> {$item['game_type']}");
                $this->line("    <fg=gray>Files:</> {$item['files']}");
                $sizeMb = round(($item['size'] ?? 0) / 1048576, 2);
                $this->line("    <fg=gray>Size:</> {$sizeMb} MB");
                $this->line("    <fg=gray>Category:</> {$item['category']}");
                $this->line("    <fg=gray>Thumbnail:</> {$item['thumbnail']}");
            }

            if (! empty($item['warnings'])) {
                foreach ($item['warnings'] as $warning) {
                    $this->line("    <fg=yellow>Warning:</> {$warning}");
                }
            }

            $this->newLine();
        }

        $this->info('Scan Summary:');
        $this->table(
            ['Discovered', 'Drafts Created', 'Drafts Updated', 'Duplicates', 'Rejected'],
            [[
                $result['discovered'],
                $result['drafts_created'],
                $result['drafts_updated'],
                $result['duplicates'],
                $result['rejected'],
            ]]
        );

        return self::SUCCESS;
    }
}
