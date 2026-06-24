<?php

namespace App\Support;

use App\Enums\EntityDimension;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class Brand
{
    /**
     * Resolve the active entity from the current Filament panel, falling back
     * to the authenticated user's entity, then to SBF.
     */
    public static function currentEntity(): EntityDimension
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId !== null && str_contains($panelId, 'chakama')) {
            return EntityDimension::Chakama;
        }

        if ($panelId !== null) {
            return EntityDimension::Sbf;
        }

        $user = Auth::user();

        if ($user instanceof User && $user->entity instanceof EntityDimension) {
            return $user->entity;
        }

        return EntityDimension::Sbf;
    }

    /**
     * Brand colour hex for documents in the current context.
     */
    public static function primaryHex(?EntityDimension $entity = null): string
    {
        return ($entity ?? self::currentEntity())->primaryHex();
    }

    /**
     * Lighter brand colour hex for the current context.
     */
    public static function primaryHexLight(?EntityDimension $entity = null): string
    {
        return ($entity ?? self::currentEntity())->primaryHexLight();
    }
}
