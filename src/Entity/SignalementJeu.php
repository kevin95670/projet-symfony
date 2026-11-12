<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Signalement d'une annonce par un utilisateur. Un utilisateur ne peut signaler un jeu qu'une seule fois.
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'UNIQ_SIGNALEMENT_JEU_AUTEUR', fields: ['jeu', 'auteur'])]
class SignalementJeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Jeu $jeu = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    // Clé du motif (ex. « contenu_trompeur »), voir SignalementController::MOTIFS
    #[ORM\Column(length: 50)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    // Vrai une fois que l'équipe a traité le signalement
    #[ORM\Column(options: ['default' => false])]
    private bool $traite = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function isTraite(): bool
    {
        return $this->traite;
    }

    public function setTraite(bool $traite): static
    {
        $this->traite = $traite;

        return $this;
    }
}