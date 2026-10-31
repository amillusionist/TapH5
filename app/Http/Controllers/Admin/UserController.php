<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a paginated listing of users for access administration.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $query = User::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role !== '') {
            $query->where('role', $role);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ],
            'roleCounts' => [
                'all' => User::count(),
                'super_admin' => User::where('role', User::ROLE_SUPER_ADMIN)->count(),
                'admin' => User::where('role', User::ROLE_ADMIN)->count(),
                'content_manager' => User::where('role', User::ROLE_CONTENT_MANAGER)->count(),
                'analyst' => User::where('role', User::ROLE_ANALYST)->count(),
                'user' => User::where('role', User::ROLE_USER)->count(),
            ],
            'availableRoles' => [
                User::ROLE_SUPER_ADMIN => 'Super Administrator (Full Platform Access)',
                User::ROLE_ADMIN => 'Administrator (Catalog & Platform Management)',
                User::ROLE_CONTENT_MANAGER => 'Content Manager (Homepage & Catalog)',
                User::ROLE_ANALYST => 'Analyst (Read-Only Analytics)',
                User::ROLE_USER => 'Standard User (Frontend Only)',
            ],
        ]);
    }

    /**
     * Update a user's role with strict privilege escalation and last-super-admin safeguards.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:super_admin,admin,content_manager,analyst,user'],
        ]);

        $newRole = $validated['role'];
        $oldRole = $user->role;

        if ($oldRole === $newRole) {
            return back()->with('info', "User '{$user->name}' already holds the '{$newRole}' role.");
        }

        // Prevent demoting the last active super_admin
        if ($oldRole === User::ROLE_SUPER_ADMIN) {
            $activeSuperAdmins = User::where('role', User::ROLE_SUPER_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdmins <= 1) {
                return back()->with('error', 'Operation blocked: Cannot demote the last remaining active Super Administrator.');
            }
        }

        $user->update(['role' => $newRole]);

        AdminAuditService::log(
            'user.role_changed',
            'User',
            (string) $user->id,
            "Changed role for user '{$user->email}' from '{$oldRole}' to '{$newRole}'",
            ['old_role' => $oldRole, 'new_role' => $newRole]
        );

        return back()->with('success', "Role for '{$user->name}' updated to '{$newRole}'.");
    }

    /**
     * Toggle a user's active/inactive status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $currentAuthUser = Auth::user();

        // Cannot deactivate yourself
        if ($currentAuthUser && $currentAuthUser->id === $user->id) {
            return back()->with('error', 'Operation blocked: You cannot deactivate your own account.');
        }

        // Cannot deactivate the last active super_admin
        if ($user->isSuperAdmin() && $user->isActive()) {
            $activeSuperAdmins = User::where('role', User::ROLE_SUPER_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdmins <= 1) {
                return back()->with('error', 'Operation blocked: Cannot deactivate the last remaining active Super Administrator.');
            }
        }

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'activated' : 'deactivated';

        AdminAuditService::log(
            'user.status_toggled',
            'User',
            (string) $user->id,
            "User '{$user->email}' was {$statusLabel}",
            ['is_active' => $newStatus]
        );

        return back()->with('success', "User '{$user->name}' has been {$statusLabel}.");
    }
}
