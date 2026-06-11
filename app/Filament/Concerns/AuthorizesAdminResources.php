<?php

namespace App\Filament\Concerns;

trait AuthorizesAdminResources
{
    protected static function permission(): string
    {
        return 'manage-products';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::permission()) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }
}
