<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminAuditService
{
    /**
     * Record an audit event for administrative actions.
     *
     * @param  array<string, mixed>|null  $details
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        string $description = '',
        ?array $details = null
    ): AdminAuditLog {
        $user = Auth::user();

        // Never store sensitive keys in details (passwords, tokens, secret keys)
        $sanitizedDetails = null;
        if ($details !== null) {
            $sanitizedDetails = self::sanitize($details);
        }

        return AdminAuditLog::create([
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => (string) $resourceId,
            'description' => $description,
            'details' => $sanitizedDetails,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Filter sensitive fields from logged details.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'remember_token', 'api_key'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = self::sanitize($value);
            }
        }

        return $data;
    }
}
