<?php

namespace App\Enums;

enum ProductUnit: string
{
    case CON = 'con';
    case KG = 'kg';
    case BO = 'bo';
    case BANH = 'banh';
    case CAI = 'cai';

    public function label(): string
    {
        return match ($this) {
            self::CON => 'Con',
            self::KG => 'Kg',
            self::BO => 'Bộ',
            self::BANH => 'Bánh',
            self::CAI => 'Cái',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $unit) => $unit->value, self::cases());
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $unit) {
            $options[$unit->value] = $unit->label();
        }

        return $options;
    }
}