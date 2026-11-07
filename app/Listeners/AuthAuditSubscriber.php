<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Schema;

class AuthAuditSubscriber
{
    public function handleUserLogin(Login $event): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        AuditTrailService::record([
            'module'                => 'auth',
            'action'                => 'auth.login',
            'entity_type'           => 'User',
            'entity_id'             => $user?->id,
            'transaction_reference' => $user ? ('USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT)) : null,
            'status'                => 'completed',
            'after_values'          => [
                'email' => $user?->email,
                'guard' => $event->guard,
            ],
            'reason'                => 'User authenticated successfully',
        ], $user);
    }

    public function handleUserLogout(Logout $event): void
    {
        if (!Schema::hasTable('audit_logs') || !$event->user) {
            return;
        }

        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        AuditTrailService::record([
            'module'                => 'auth',
            'action'                => 'auth.logout',
            'entity_type'           => 'User',
            'entity_id'             => $user?->id,
            'transaction_reference' => $user ? ('USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT)) : null,
            'status'                => 'completed',
            'after_values'          => [
                'email' => $user?->email,
                'guard' => $event->guard,
            ],
            'reason'                => 'User logged out of session',
        ], $user);
    }

    public function handleFailedLogin(Failed $event): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;
        $attemptedEmail = $event->credentials['email'] ?? 'unknown';

        AuditTrailService::record([
            'actor_user_id'          => $user?->id,
            'actor_name_snapshot'    => $user?->name ?? ('Failed Login (' . $attemptedEmail . ')'),
            'actor_role_snapshot'    => $user?->role?->role_name ?? 'Unauthenticated',
            'module'                 => 'auth',
            'action'                 => 'auth.failed',
            'entity_type'            => 'User',
            'entity_id'              => $user?->id,
            'transaction_reference'  => $user ? ('USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT)) : null,
            'status'                 => 'failed',
            'after_values'           => [
                'attempted_email' => $attemptedEmail,
                'guard'           => $event->guard,
            ],
            'reason'                 => 'Failed login attempt (invalid credentials)',
        ], $user);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        AuditTrailService::record([
            'module'                => 'auth',
            'action'                => 'auth.password_reset',
            'entity_type'           => 'User',
            'entity_id'             => $user?->id,
            'transaction_reference' => $user ? ('USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT)) : null,
            'status'                => 'completed',
            'after_values'          => [
                'email'          => $user?->email,
                'password_reset' => true,
            ],
            'reason'                => 'User password reset completed',
        ], $user);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class         => 'handleUserLogin',
            Logout::class        => 'handleUserLogout',
            Failed::class        => 'handleFailedLogin',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
