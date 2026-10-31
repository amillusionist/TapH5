<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const ROLE_ANALYST = 'analyst';

    public const ROLE_USER = 'user';

    /**
     * Determine if the user holds super administrator privileges.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Determine if the user holds full or general administrator role.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true);
    }

    /**
     * Determine if user is a content manager.
     */
    public function isContentManager(): bool
    {
        return $this->role === self::ROLE_CONTENT_MANAGER;
    }

    /**
     * Determine if user is an analyst.
     */
    public function isAnalyst(): bool
    {
        return $this->role === self::ROLE_ANALYST;
    }

    /**
     * Determine if user has any administrative staff access to the admin panel.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
            self::ROLE_ANALYST,
        ], true);
    }

    /**
     * Permission: Manage Users and Roles (Super Admin only).
     */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Permission: Manage Platform Settings & Maintenance (Super Admin only).
     */
    public function canManageSettings(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Permission: Manage Homepage, Games, Categories, Tags.
     */
    public function canManageContent(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
        ], true);
    }

    /**
     * Permission: Approve and publish games to the public catalog (Super Admin & Admin).
     */
    public function canPublishGames(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ], true);
    }

    /**
     * Permission: Review game submissions (Super Admin, Admin, Content Manager).
     */
    public function canReviewGames(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
        ], true);
    }

    /**
     * Permission: Manage Ad Providers and Ad Placements.
     */
    public function canManageMonetization(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ], true);
    }

    /**
     * Permission: View Analytics and Reports.
     */
    public function canViewAnalytics(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_ANALYST,
        ], true);
    }

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active !== null ? (bool) $this->is_active : true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
