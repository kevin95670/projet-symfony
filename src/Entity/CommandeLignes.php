<?php

namespace App\Entity;

use App\Enum\StatutLigne;
use App\Repository\CommandeLignesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeLignesRepository::class)]
class CommandeLignes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $PrixUnitaire = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'commandeLignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Jeu $jeu = null;

    #[ORM\ManyToOne(inversedBy: 'commandeLignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    // Statut de l'envoi de ce jeu (chaque vendeur gère ses propres lignes)
    #[ORM\Column(enumType: StatutLigne::class, options: ['default' => 'en_preparation'])]
    private StatutLigne $statut = StatutLigne::EN_PREPARATION;

    // Numéro de suivi saisi par le vendeur à l'expédition
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroSuivi = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->PrixUnitaire;
    }

    public function setPrixUnitaire(string $PrixUnitaire): static
    {
        $this->PrixUnitaire = $PrixUnitaire;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getJeu(): ?Jeu
    {
        return $this->jeu;
    }

    public function setJeu(?Jeu $jeu): static
    {
        $this->jeu = $jeu;

        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getStatut(): StatutLigne
    {
        return $this->statut;
    }

    public function setStatut(StatutLigne $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNumeroSuivi(): ?string
    {
        return $this->numeroSuivi;
    }

    public function setNumeroSuivi(?string $numeroSuivi): static
    {
        $this->numeroSuivi = $numeroSuivi;

        return $this;
    }
}