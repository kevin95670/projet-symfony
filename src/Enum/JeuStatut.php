<?php

namespace App\Enum;

enum JeuStatut: string
{
    case EN_VENTE = 'disponible';
    case VENDU = 'vendu';
    case RETIRE = 'retire';

    public function label(): string
    {
        return match($this) {
            self::EN_VENTE => 'En vente',
            self::VENDU => 'Vendu',
            self::RETIRE => 'Retiré',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::EN_VENTE => 'Le jeu est actuellement en vente et disponible à l\'achat.',
            self::VENDU => 'Le jeu a été vendu et n\'est plus disponible.',
            self::RETIRE => 'Le jeu a été retiré de la vente et n\'est plus disponible.',
        };
    }
}