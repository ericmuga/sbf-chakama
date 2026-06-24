<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum EntityDimension: string
{
    case Chakama = 'chakama';
    case Sbf = 'sbf';

    public function label(): string
    {
        return match ($this) {
            self::Chakama => 'Chakama Ranch',
            self::Sbf => 'SOBA Benevolent Fund',
        };
    }

    /**
     * Primary brand colour as a hex string, used for document headers/accents.
     */
    public function primaryHex(): string
    {
        return match ($this) {
            self::Chakama => '#047857',
            self::Sbf => '#0B2447',
        };
    }

    /**
     * A slightly lighter shade of the brand colour for borders/hovers.
     */
    public function primaryHexLight(): string
    {
        return match ($this) {
            self::Chakama => '#059669',
            self::Sbf => '#13315C',
        };
    }

    /**
     * Generated Filament colour palette for the panel primary colour.
     *
     * @return array<int|string, string>
     */
    public function palette(): array
    {
        return Color::hex($this->primaryHex());
    }
}
