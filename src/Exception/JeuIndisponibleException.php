<?php

namespace App\Exception;

use App\Entity\Jeu;

// Levée quand un jeu du panier n'est plus en vente au moment de la commande
final class JeuIndisponibleException extends \RuntimeException
{
    public function __construct(public readonly Jeu $jeu)
    {
        parent::__construct(sprintf('Le jeu "%s" n\'est plus disponible.', $jeu->getTitre()));
    }
}