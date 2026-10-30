<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AnalyticsEventController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Ingest and record a first-party analytics event.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:game_viewed,game_play_clicked,game_loaded,game_session_ping,game_session_ended'],
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'visitor_id' => ['nullable', 'string', 'max:64'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'device_type' => ['nullable', 'string', 'in:desktop,mobile,tablet,other'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Resolve or generate first-party pseudonymized visitor ID
        $rawVisitorId = $request->cookie('taph5_vid')
            ?: ($validated['visitor_id'] ?? null)
            ?: $request->header('X-Visitor-Id')
            ?: (string) Str::uuid();

        // Sanitize visitor ID to alphanumeric string with dashes
        $visitorId = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $rawVisitorId), 0, 64);
        if (empty($visitorId)) {
            $visitorId = (string) Str::uuid();
        }

        try {
            $event = $this->analyticsService->recordEvent(
                eventType: $validated['event_type'],
                gameId: (int) $validated['game_id'],
                visitorId: $visitorId,
                sessionId: $validated['session_id'] ?? null,
                deviceType: $validated['device_type'] ?? 'desktop',
                durationSeconds: (int) ($validated['duration_seconds'] ?? 0),
                metadata: $validated['metadata'] ?? null
            );

            $response = response()->json([
                'status' => 'recorded',
                'event_id' => $event?->id,
                'visitor_id' => $visitorId,
            ], 200);

            // Attach 1-year first-party cookie
            return $response->withCookie(
                cookie(
                    name: 'taph5_vid',
                    value: $visitorId,
                    minutes: 60 * 24 * 365,
                    path: '/',
                    domain: null,
                    secure: $request->isSecure(),
                    httpOnly: false,
                    raw: false,
                    sameSite: 'Lax'
                )
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
