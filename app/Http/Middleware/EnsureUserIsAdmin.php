<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isStaff()) {
            abort(403, 'Access denied. Administrative privileges required.');
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Your account has been deactivated. Please contact an administrator.');
        }

        if ($permission === 'super_admin' && ! $user->isSuperAdmin()) {
            abort(403, 'Access denied. Super Administrator privileges required.');
        }

        if ($permission === 'manage_users' && ! $user->canManageUsers()) {
            abort(403, 'Access denied. You do not have permission to manage users.');
        }

        if ($permission === 'manage_settings' && ! $user->canManageSettings()) {
            abort(403, 'Access denied. You do not have permission to manage platform settings.');
        }

        if ($permission === 'manage_content' && ! $user->canManageContent()) {
            abort(403, 'Access denied. You do not have permission to manage content.');
        }

        if ($permission === 'publish_games' && ! $user->canPublishGames()) {
            abort(403, 'Access denied. You do not have permission to publish games.');
        }

        if ($permission === 'review_games' && ! $user->canReviewGames()) {
            abort(403, 'Access denied. You do not have permission to review games.');
        }

        if ($permission === 'manage_monetization' && ! $user->canManageMonetization()) {
            abort(403, 'Access denied. You do not have permission to manage monetization.');
        }

        if ($permission === 'view_analytics' && ! $user->canViewAnalytics()) {
            abort(403, 'Access denied. You do not have permission to view analytics.');
        }

        return $next($request);
    }
}
