/**
 * TapH5 Games — First-Party Analytics Service
 *
 * Privacy-friendly, first-party telemetry tracking:
 * - Zero third-party trackers or external cookies.
 * - Anonymous, pseudonymized visitor ID stored in first-party cookie / localStorage.
 * - Tracks view, play click, iframe confirmation, and active engagement duration.
 */

export type AnalyticsEventType =
    | 'game_viewed'
    | 'game_play_clicked'
    | 'game_loaded'
    | 'game_session_ping'
    | 'game_session_ended';

export type DeviceType = 'desktop' | 'mobile' | 'tablet' | 'other';

export interface AnalyticsPayload {
    event_type: AnalyticsEventType;
    game_id: number;
    visitor_id?: string;
    session_id?: string;
    device_type?: DeviceType;
    duration_seconds?: number;
    metadata?: Record<string, any> | null;
}

/**
 * Generate a cryptographically strong UUID or fallback.
 */
function generateUuid(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    return 'taph5-' + Math.random().toString(36).substring(2, 15) + '-' + Date.now().toString(36);
}

/**
 * Get or initialize persistent first-party anonymous visitor ID.
 */
export function getVisitorId(): string {
    if (typeof window === 'undefined') return '';

    try {
        let vid = localStorage.getItem('taph5_vid');
        if (!vid) {
            vid = generateUuid();
            localStorage.setItem('taph5_vid', vid);
        }
        return vid;
    } catch {
        return generateUuid();
    }
}

/**
 * Get or initialize per-session identifier (cleared when browser session ends).
 */
export function getSessionId(): string {
    if (typeof window === 'undefined') return '';

    try {
        let sid = sessionStorage.getItem('taph5_sid');
        if (!sid) {
            sid = generateUuid();
            sessionStorage.setItem('taph5_sid', sid);
        }
        return sid;
    } catch {
        return generateUuid();
    }
}

/**
 * Detect client device category without user agent fingerprinting.
 */
export function getDeviceType(): DeviceType {
    if (typeof window === 'undefined') return 'desktop';

    const width = window.innerWidth;
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (width < 640 || (isTouch && width < 768)) {
        return 'mobile';
    }

    if (width < 1024 && isTouch) {
        return 'tablet';
    }

    return 'desktop';
}

/**
 * Dispatch an analytics event to the TapH5 first-party endpoint.
 */
export async function trackEvent(
    eventType: AnalyticsEventType,
    gameId: number,
    options: { durationSeconds?: number; metadata?: Record<string, any> } = {}
): Promise<void> {
    if (typeof window === 'undefined' || !gameId) return;

    const payload: AnalyticsPayload = {
        event_type: eventType,
        game_id: gameId,
        visitor_id: getVisitorId(),
        session_id: getSessionId(),
        device_type: getDeviceType(),
        duration_seconds: options.durationSeconds ?? 0,
        metadata: options.metadata ?? null,
    };

    const endpoint = '/api/analytics/events';

    try {
        if (typeof fetch === 'function') {
            await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Visitor-Id': payload.visitor_id || '',
                },
                body: JSON.stringify(payload),
                keepalive: true,
            });
            return;
        }

        if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
        }
    } catch {
        // Suppress telemetry errors silently to guarantee gameplay is never affected
    }
}

/**
 * Synchronous beacon dispatch helper for window unmount / pagehide.
 */
function sendBeaconEvent(payload: AnalyticsPayload): void {
    const endpoint = '/api/analytics/events';
    const json = JSON.stringify(payload);

    try {
        if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
            const blob = new Blob([json], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
            return;
        }

        if (typeof fetch === 'function') {
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: json,
                keepalive: true,
            }).catch(() => {});
        }
    } catch {
        // Silently ignore
    }
}

/**
 * Stateful game session tracker managing lifecycle, heartbeats, and engagement duration.
 */
export class GameSessionTracker {
    private gameId: number;
    private isPlaying = false;
    private lastPingTime = 0;
    private accumulatedDuration = 0;
    private timerId: ReturnType<typeof setInterval> | null = null;
    private isDestroyed = false;

    constructor(gameId: number) {
        this.gameId = gameId;
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        this.handlePageHide = this.handlePageHide.bind(this);

        if (typeof document !== 'undefined') {
            document.addEventListener('visibilitychange', this.handleVisibilityChange);
        }
        if (typeof window !== 'undefined') {
            window.addEventListener('pagehide', this.handlePageHide);
            window.addEventListener('beforeunload', this.handlePageHide);
        }
    }

    /**
     * Track initial page view of the game details page.
     */
    public recordView(): void {
        trackEvent('game_viewed', this.gameId);
    }

    /**
     * Track when user presses "Play Now" or reloads the game.
     */
    public recordPlayClick(): void {
        trackEvent('game_play_clicked', this.gameId);
    }

    /**
     * Track when iframe confirms load and begin gameplay heartbeat.
     */
    public recordGameLoaded(): void {
        trackEvent('game_loaded', this.gameId);
        this.startEngagement();
    }

    /**
     * Start or resume engagement heartbeat loop.
     */
    private startEngagement(): void {
        if (this.isPlaying || this.isDestroyed) return;

        this.isPlaying = true;
        this.lastPingTime = Date.now();

        // 15-second heartbeat interval while active
        this.timerId = setInterval(() => {
            this.flushHeartbeat();
        }, 15000);
    }

    /**
     * Flush elapsed active time to server.
     */
    private flushHeartbeat(isFinal = false): void {
        if (!this.isPlaying || document.hidden) return;

        const now = Date.now();
        const deltaSeconds = Math.max(0, Math.round((now - this.lastPingTime) / 1000));
        this.lastPingTime = now;

        if (deltaSeconds > 0) {
            this.accumulatedDuration += deltaSeconds;
            const eventType = isFinal ? 'game_session_ended' : 'game_session_ping';

            if (isFinal) {
                sendBeaconEvent({
                    event_type: eventType,
                    game_id: this.gameId,
                    visitor_id: getVisitorId(),
                    session_id: getSessionId(),
                    device_type: getDeviceType(),
                    duration_seconds: deltaSeconds,
                });
            } else {
                trackEvent(eventType, this.gameId, { durationSeconds: deltaSeconds });
            }
        }
    }

    /**
     * Handle tab visibility switches (pause timer when hidden, resume when visible).
     */
    private handleVisibilityChange(): void {
        if (!this.isPlaying) return;

        if (document.hidden) {
            this.flushHeartbeat(false);
        } else {
            this.lastPingTime = Date.now();
        }
    }

    /**
     * Handle page navigation or tab close.
     */
    private handlePageHide(): void {
        if (!this.isPlaying || this.isDestroyed) return;
        this.flushHeartbeat(true);
    }

    /**
     * Clean up timers, listeners, and flush any remaining gameplay time.
     */
    public destroy(): void {
        if (this.isDestroyed) return;

        if (this.isPlaying) {
            this.flushHeartbeat(true);
        }

        this.isDestroyed = true;
        this.isPlaying = false;

        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }

        if (typeof document !== 'undefined') {
            document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        }
        if (typeof window !== 'undefined') {
            window.removeEventListener('pagehide', this.handlePageHide);
            window.removeEventListener('beforeunload', this.handlePageHide);
        }
    }
}

