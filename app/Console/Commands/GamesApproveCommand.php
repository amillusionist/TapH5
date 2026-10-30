<?php

namespace App\Console\Commands;

use App\Services\Games\GamePublisherService;
use Illuminate\Console\Command;

class GamesApproveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:approve
                            {game : The ID or slug of the draft game to approve and publish}
                            {--force : Force publishing even if non-fatal warnings exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Approve a staged draft game and publish it to public game storage';

    /**
     * Execute the console command.
     */
    public function handle(GamePublisherService $publisherService): int
    {
        $gameIdentifier = (string) $this->argument('game');
        $force = (bool) $this->option('force');

        $this->info("Initiating approval workflow for game: '{$gameIdentifier}'...");

        $result = $publisherService->approveAndPublish($gameIdentifier, $force);

        if (! $result['success']) {
            $this->error('Approval Failed: '.$result['message']);

            return self::FAILURE;
        }

        $game = $result['game'];
        $this->info('✓ Game Successfully Approved & Published!');
        $this->line("<comment>Game Title:</comment> {$game?->title}");
        $this->line("<comment>Status:</comment> <fg=green>{$game?->status}</>");
        $this->line("<comment>Public Storage Path:</comment> {$result['public_storage_path']}");
        $this->line("<comment>Game Embed URL:</comment> <fg=cyan>{$result['public_url']}</>");

        return self::SUCCESS;
    }
}
