<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// Un seul avis par commande et par vendeur
#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_AVIS_COMMANDE_VENDEUR', fields: ['commande', 'vendeur'])]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'avisEnvoyes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\ManyToOne(inversedBy: 'avisRecus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $vendeur = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAvis = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getVendeur(): ?User
    {
        return $this->vendeur;
    }

    public function setVendeur(?User $vendeur): static
    {
        $this->vendeur = $vendeur;

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

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

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

    public function getDateAvis(): ?\DateTimeImmutable
    {
        return $this->dateAvis;
    }

    public function setDateAvis(\DateTimeImmutable $dateAvis): static
    {
        $this->dateAvis = $dateAvis;

        return $this;
    }
}