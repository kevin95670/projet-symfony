<?php

namespace App\Enum;

enum Pegi: int
{
    case PEGI_3 = 3;
    case PEGI_7 = 7;
    case PEGI_12 = 12;
    case PEGI_16 = 16;
    case PEGI_18 = 18;

    public function label(): string
    {
        return match($this) {
            self::PEGI_3 => 'PEGI 3',
            self::PEGI_7 => 'PEGI 7',
            self::PEGI_12 => 'PEGI 12',
            self::PEGI_16 => 'PEGI 16',
            self::PEGI_18 => 'PEGI 18',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PEGI_3 => 'Convient à tous les âges',
            self::PEGI_7 => 'Peut contenir des scènes effrayantes',
            self::PEGI_12 => 'Violence légère envers des personnages fantastiques',
            self::PEGI_16 => 'Violence réaliste, langage grossier',
            self::PEGI_18 => 'Réservé aux adultes',
        };
    }
}