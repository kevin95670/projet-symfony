<?php

namespace App\Enum;

enum Etat: string
{
    case NEUF = 'neuf';
    case TRES_BON = 'très bon';
    case BON = 'bon état';
    case MAUVAIS = 'mauvais état';

    public function label(): string
    {
        return match($this) {
            self::NEUF => 'Neuf',
            self::TRES_BON => 'Très bon',
            self::BON => 'Bon état',
            self::MAUVAIS => 'Mauvais état',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::NEUF => 'Le jeu est en parfait état, comme neuf.',
            self::TRES_BON => 'Le jeu est en très bon état, avec peu de signes d\'usure.',
            self::BON => 'Le jeu est en bon état, avec des signes d\'usure visibles.',
            self::MAUVAIS => 'Le jeu est en mauvais état, avec des dommages importants.',
        };
    }
}