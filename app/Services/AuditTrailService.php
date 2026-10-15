<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditTrailService
{
    /**
     * Sensitive attribute keys that must never be persisted in audit logs.
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'token',
        'secret',
        'credit_card',
        'cvv',
    ];

    /**
     * Request-scoped correlation ID for tracing multi-step atomic operations.
     */
    private static ?string $currentCorrelationId = null;

    /**
     * Retrieve or generate the correlation ID for the current request lifecycle.
     */
    public static function correlationId(): string
    {
        if (self::$currentCorrelationId === null) {
            self::$currentCorrelationId = (string) Str::uuid();
        }

        return self::$currentCorrelationId;
    }

    /**
     * Reset correlation ID (useful between isolated test requests).
     */
    public static function resetCorrelationId(): void
    {
        self::$currentCorrelationId = null;
    }

    /**
     * Record a tamper-resistant audit event using server-side authentication context.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function record(array $payload, ?User $explicitActor = null): AuditLog
    {
        /** @var User|null $actor */
        $actor = $explicitActor ?? Auth::user();

        if ($actor && !$actor->relationLoaded('role')) {
            $actor->load('role');
        }

        $approverId = $payload['approver_user_id'] ?? null;
        $approverName = $payload['approver_name_snapshot'] ?? null;

        if ($approverId && empty($approverName)) {
            $approver = User::withTrashed()->find($approverId);
            $approverName = $approver?->name;
        }

        $request = app()->bound('request') ? request() : null;

        return AuditLog::create([
            'event_uuid'             => (string) Str::uuid(),
            'correlation_id'         => $payload['correlation_id'] ?? self::correlationId(),
            'actor_user_id'          => $actor?->id ?? ($payload['actor_user_id'] ?? null),
            'actor_name_snapshot'    => $actor?->name ?? ($payload['actor_name_snapshot'] ?? 'System / Guest'),
            'actor_role_snapshot'    => $actor?->role?->role_name ?? ($payload['actor_role_snapshot'] ?? 'Unauthenticated'),
            'module'                 => $payload['module'],
            'action'                 => $payload['action'],
            'entity_type'            => $payload['entity_type'] ?? null,
            'entity_id'              => isset($payload['entity_id']) ? (string) $payload['entity_id'] : null,
            'transaction_reference'  => $payload['transaction_reference'] ?? null,
            'status'                 => $payload['status'] ?? 'completed',
            'approval_status'        => $payload['approval_status'] ?? 'not_required',
            'approver_user_id'       => $approverId,
            'approver_name_snapshot' => $approverName,
            'approved_at'            => $payload['approved_at'] ?? null,
            'before_values'          => isset($payload['before_values']) ? self::sanitizeValues($payload['before_values']) : null,
            'after_values'           => isset($payload['after_values']) ? self::sanitizeValues($payload['after_values']) : null,
            'reason'                 => $payload['reason'] ?? null,
            'ip_address'             => $payload['ip_address'] ?? $request?->ip(),
            'user_agent'             => $payload['user_agent'] ?? $request?->userAgent(),
            'source_channel'         => $payload['source_channel'] ?? (app()->runningInConsole() ? 'console' : 'web'),
        ]);
    }

    /**
     * Recursively strip passwords, tokens, and credentials from before/after state arrays.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function sanitizeValues(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeValues($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
