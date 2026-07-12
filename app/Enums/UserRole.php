<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdministrator = 'super_administrator';
    case Administrator = 'administrator';
    case Support = 'support';
    case Moderator = 'moderator';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Super Administrator',
            self::Administrator => 'Administrator',
            self::Support => 'Support',
            self::Moderator => 'Moderator',
            self::User => 'User',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [
            self::SuperAdministrator,
            self::Administrator,
            self::Support,
            self::Moderator,
        ]);
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [
            self::SuperAdministrator,
            self::Administrator,
        ]);
    }

    public function canManageContent(): bool
    {
        return in_array($this, [
            self::SuperAdministrator,
            self::Administrator,
            self::Moderator,
        ]);
    }

    public function canViewAnalytics(): bool
    {
        return in_array($this, [
            self::SuperAdministrator,
            self::Administrator,
            self::Support,
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdministrator;
    }
}
