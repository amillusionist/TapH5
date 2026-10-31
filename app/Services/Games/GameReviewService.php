<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Models\GameReview;
use App\Models\User;
use App\Services\Admin\AdminAuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GameReviewService
{
    public function __construct(
        protected GamePublisherService $publisherService,
    ) {}

    /**
     * Submit a draft game for editorial review.
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function submitForReview(Game $game, User $user, ?string $notes = null): array
    {
        if ($game->status === 'published') {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already published.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_PENDING) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already pending review.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_APPROVED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already approved for release.",
                'game' => $game,
            ];
        }

        $expectedStatus = $game->review_status;

        $result = DB::transaction(function () use ($game, $user, $notes, $expectedStatus) {
            $locked = Game::where('id', $game->id)->lockForUpdate()->first();
            if ($locked->review_status !== $expectedStatus) {
                return [
                    'success' => false,
                    'message' => "Concurrent update detected: review state changed to '{$locked->review_status}' by another user.",
                    'game' => $locked,
                ];
            }

            $locked->update([
                'review_status' => Game::REVIEW_PENDING,
                'submitted_at' => Carbon::now(),
                'submitted_by' => $user->id,
                'review_notes' => $notes,
            ]);

            GameReview::create([
                'game_id' => $locked->id,
                'user_id' => $user->id,
                'action' => GameReview::ACTION_SUBMITTED,
                'from_status' => $expectedStatus,
                'to_status' => Game::REVIEW_PENDING,
                'notes' => $notes ?: 'Submitted game for content review.',
                'metadata' => [
                    'submitted_by_name' => $user->name,
                    'submitted_by_email' => $user->email,
                ],
            ]);

            return [
                'success' => true,
                'message' => "Game '{$locked->title}' submitted for review successfully.",
                'game' => $locked->fresh(),
            ];
        });

        if ($result['success']) {
            AdminAuditService::log(
                'game_review.submitted',
                'Game',
                (string) $game->id,
                "Submitted game '{$game->title}' for review",
                ['notes' => $notes]
            );
        }

        return $result;
    }

    /**
     * Approve a game in the review queue (without auto-publishing).
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function approve(Game $game, User $user, ?string $notes = null): array
    {
        if ($game->status === 'published') {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already published.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_REJECTED) {
            return [
                'success' => false,
                'message' => "Rejected game '{$game->title}' cannot be approved directly. Revisions must be submitted first.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_NOT_SUBMITTED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' must be submitted for review before it can be approved.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_APPROVED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already approved.",
                'game' => $game,
            ];
        }

        $expectedStatus = $game->review_status;

        $result = DB::transaction(function () use ($game, $user, $notes, $expectedStatus) {
            $locked = Game::where('id', $game->id)->lockForUpdate()->first();
            if ($locked->review_status !== $expectedStatus) {
                return [
                    'success' => false,
                    'message' => "Concurrent update detected: review state changed to '{$locked->review_status}' by another user.",
                    'game' => $locked,
                ];
            }

            $locked->update([
                'review_status' => Game::REVIEW_APPROVED,
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => $user->id,
                'review_notes' => $notes,
            ]);

            GameReview::create([
                'game_id' => $locked->id,
                'user_id' => $user->id,
                'action' => GameReview::ACTION_APPROVED,
                'from_status' => $expectedStatus,
                'to_status' => Game::REVIEW_APPROVED,
                'notes' => $notes ?: 'Game approved for publication.',
                'metadata' => [
                    'reviewer_name' => $user->name,
                    'reviewer_email' => $user->email,
                ],
            ]);

            return [
                'success' => true,
                'message' => "Game '{$locked->title}' approved successfully.",
                'game' => $locked->fresh(),
            ];
        });

        if ($result['success']) {
            AdminAuditService::log(
                'game_review.approved',
                'Game',
                (string) $game->id,
                "Approved game '{$game->title}' in review queue",
                ['notes' => $notes]
            );
        }

        return $result;
    }

    /**
     * Approve and publish a game immediately in one step (Super Admin / Admin only).
     *
     * @return array{success: bool, message: string, game: Game|null}
     */
    public function approveAndPublish(Game $game, User $user, ?string $notes = null): array
    {
        if (! $user->canPublishGames()) {
            return [
                'success' => false,
                'message' => 'Unauthorized: you do not have permission to publish games directly.',
                'game' => $game,
            ];
        }

        if ($game->status === 'published') {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already published.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_REJECTED) {
            return [
                'success' => false,
                'message' => "Rejected game '{$game->title}' cannot be published directly without revision and approval.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_CHANGES_REQUESTED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' has changes requested and must be approved before publishing.",
                'game' => $game,
            ];
        }

        // If not already approved, approve first
        if ($game->review_status !== Game::REVIEW_APPROVED) {
            $approveResult = $this->approve($game, $user, $notes);
            if (! $approveResult['success']) {
                return $approveResult;
            }
            $game = $approveResult['game'];
        }

        // Then execute atomic publishing pipeline
        $publishResult = $this->publisherService->approveAndPublish($game->id);
        if (! $publishResult['success']) {
            return [
                'success' => false,
                'message' => 'Publishing failed: '.$publishResult['message'],
                'game' => $game->fresh(),
            ];
        }

        GameReview::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'action' => GameReview::ACTION_PUBLISHED,
            'from_status' => Game::REVIEW_APPROVED,
            'to_status' => 'published',
            'notes' => $notes ?: 'Game approved and published to live catalog.',
            'metadata' => [
                'public_storage_path' => $publishResult['public_storage_path'],
                'public_url' => $publishResult['public_url'],
            ],
        ]);

        AdminAuditService::log(
            'game_review.published',
            'Game',
            (string) $game->id,
            "Approved and published game '{$game->title}' to live catalog",
            ['notes' => $notes, 'public_url' => $publishResult['public_url']]
        );

        return [
            'success' => true,
            'message' => "Game '{$game->title}' approved and published to live catalog.",
            'game' => $game->fresh(),
        ];
    }

    /**
     * Request changes on a submitted game.
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function requestChanges(Game $game, User $user, string $notes): array
    {
        if ($game->status === 'published') {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already published.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_NOT_SUBMITTED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' has not been submitted for review.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_REJECTED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already rejected.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_APPROVED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already approved.",
                'game' => $game,
            ];
        }

        $expectedStatus = $game->review_status;

        $result = DB::transaction(function () use ($game, $user, $notes, $expectedStatus) {
            $locked = Game::where('id', $game->id)->lockForUpdate()->first();
            if ($locked->review_status !== $expectedStatus) {
                return [
                    'success' => false,
                    'message' => "Concurrent update detected: review state changed to '{$locked->review_status}' by another user.",
                    'game' => $locked,
                ];
            }

            $locked->update([
                'review_status' => Game::REVIEW_CHANGES_REQUESTED,
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => $user->id,
                'review_notes' => $notes,
            ]);

            GameReview::create([
                'game_id' => $locked->id,
                'user_id' => $user->id,
                'action' => GameReview::ACTION_CHANGES_REQUESTED,
                'from_status' => $expectedStatus,
                'to_status' => Game::REVIEW_CHANGES_REQUESTED,
                'notes' => $notes,
                'metadata' => [
                    'reviewer_name' => $user->name,
                    'reviewer_email' => $user->email,
                ],
            ]);

            return [
                'success' => true,
                'message' => "Changes requested for '{$locked->title}'.",
                'game' => $locked->fresh(),
            ];
        });

        if ($result['success']) {
            AdminAuditService::log(
                'game_review.changes_requested',
                'Game',
                (string) $game->id,
                "Requested changes for game '{$game->title}'",
                ['notes' => $notes]
            );
        }

        return $result;
    }

    /**
     * Reject a submitted game with a documented reason.
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function reject(Game $game, User $user, string $reason): array
    {
        if ($game->status === 'published') {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already published.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_REJECTED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already rejected.",
                'game' => $game,
            ];
        }

        $expectedStatus = $game->review_status;

        $result = DB::transaction(function () use ($game, $user, $reason, $expectedStatus) {
            $locked = Game::where('id', $game->id)->lockForUpdate()->first();
            if ($locked->review_status !== $expectedStatus) {
                return [
                    'success' => false,
                    'message' => "Concurrent update detected: review state changed to '{$locked->review_status}' by another user.",
                    'game' => $locked,
                ];
            }

            $locked->update([
                'review_status' => Game::REVIEW_REJECTED,
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => $user->id,
                'review_notes' => $reason,
            ]);

            GameReview::create([
                'game_id' => $locked->id,
                'user_id' => $user->id,
                'action' => GameReview::ACTION_REJECTED,
                'from_status' => $expectedStatus,
                'to_status' => Game::REVIEW_REJECTED,
                'notes' => $reason,
                'metadata' => [
                    'reviewer_name' => $user->name,
                    'reviewer_email' => $user->email,
                ],
            ]);

            return [
                'success' => true,
                'message' => "Game '{$locked->title}' rejected.",
                'game' => $locked->fresh(),
            ];
        });

        if ($result['success']) {
            AdminAuditService::log(
                'game_review.rejected',
                'Game',
                (string) $game->id,
                "Rejected game '{$game->title}' in review queue",
                ['reason' => $reason]
            );
        }

        return $result;
    }

    /**
     * Return game to draft status.
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function returnToDraft(Game $game, User $user, ?string $notes = null): array
    {
        if ($game->status === 'published') {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already published.",
                'game' => $game,
            ];
        }

        if ($game->review_status === Game::REVIEW_NOT_SUBMITTED) {
            return [
                'success' => false,
                'message' => "Game '{$game->title}' is already in draft state.",
                'game' => $game,
            ];
        }

        $expectedStatus = $game->review_status;

        $result = DB::transaction(function () use ($game, $user, $notes, $expectedStatus) {
            $locked = Game::where('id', $game->id)->lockForUpdate()->first();
            if ($locked->review_status !== $expectedStatus) {
                return [
                    'success' => false,
                    'message' => "Concurrent update detected: review state changed to '{$locked->review_status}' by another user.",
                    'game' => $locked,
                ];
            }

            $locked->update([
                'review_status' => Game::REVIEW_NOT_SUBMITTED,
                'review_notes' => $notes,
            ]);

            GameReview::create([
                'game_id' => $locked->id,
                'user_id' => $user->id,
                'action' => GameReview::ACTION_RETURNED_TO_DRAFT,
                'from_status' => $expectedStatus,
                'to_status' => Game::REVIEW_NOT_SUBMITTED,
                'notes' => $notes ?: 'Returned game to draft status.',
                'metadata' => [
                    'actor_name' => $user->name,
                ],
            ]);

            return [
                'success' => true,
                'message' => "Game '{$locked->title}' returned to draft status.",
                'game' => $locked->fresh(),
            ];
        });

        if ($result['success']) {
            AdminAuditService::log(
                'game_review.returned_to_draft',
                'Game',
                (string) $game->id,
                "Returned game '{$game->title}' to draft status",
                ['notes' => $notes]
            );
        }

        return $result;
    }

    /**
     * Get count of games currently pending review.
     */
    public function getPendingReviewCount(): int
    {
        return Game::where('status', 'draft')
            ->where('review_status', Game::REVIEW_PENDING)
            ->count();
    }
}
