<?php

namespace App\Enum;

enum StatutLigne: string
{
    case EN_PREPARATION = 'en_preparation';
    case EXPEDIEE = 'expediee';
    case RECUE = 'recue';

    public function label(): string
    {
        return match ($this) {
            self::EN_PREPARATION => 'En préparation',
            self::EXPEDIEE => 'Expédiée',
            self::RECUE => 'Reçue',
        };
    }

    // Statut suivant, utilisé par le bouton du vendeur (null si déjà reçue)
    public function suivant(): ?self
    {
        return match ($this) {
            self::EN_PREPARATION => self::EXPEDIEE,
            self::EXPEDIEE => self::RECUE,
            self::RECUE => null,
        };
    }
}