<?php

namespace App\Entity;

use App\Enum\JeuStatut;
use App\Enum\Etat;
use App\Enum\Pegi;
use App\Enum\Plateforme;
use App\Repository\JeuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JeuRepository::class)]
class Jeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_creation = null;

    #[ORM\Column]
    private ?Plateforme $plateforme = null;

    #[ORM\Column]
    private ?Pegi $pegi = null;

    #[ORM\Column(length: 255)]
    private ?Etat $etat = null;

    #[ORM\Column(length: 255)]
    private ?JeuStatut $statut = null;

    #[ORM\ManyToOne(inversedBy: 'jeux')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $vendeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /**
     * @var Collection<int, Categorie>
     */
    #[ORM\ManyToMany(targetEntity: Categorie::class, mappedBy: 'jeux')]
    private Collection $categories;

    /**
     * @var Collection<int, PanierItem>
     */
    #[ORM\OneToMany(targetEntity: PanierItem::class, mappedBy: 'jeu')]
    private Collection $panierItems;

    /**
     * @var Collection<int, CommandeLignes>
     */
    #[ORM\OneToMany(targetEntity: CommandeLignes::class, mappedBy: 'jeu')]
    private Collection $commandeLignes;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    /**
     * @var Collection<int, WishlistItem>
     */
    #[ORM\OneToMany(targetEntity: WishlistItem::class, mappedBy: 'jeu')]
    private Collection $wishlistItems;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $mise_a_jour = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->panierItems = new ArrayCollection();
        $this->commandeLignes = new ArrayCollection();
        $this->wishlistItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getPlateforme(): ?Plateforme
    {
        return $this->plateforme;
    }

    public function setPlateforme(Plateforme $plateforme): static
    {
        $this->plateforme = $plateforme;

        return $this;
    }

    public function getPegi(): ?Pegi
    {
        return $this->pegi;
    }

    public function setPegi(Pegi $pegi): static
    {
        $this->pegi = $pegi;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getStatut(): ?JeuStatut
    {
        return $this->statut;
    }

    public function setStatut(JeuStatut $statut): static
    {
        $this->statut = $statut;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Categorie>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Categorie $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addJeux($this);
        }

        return $this;
    }

    public function removeCategory(Categorie $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeJeux($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PanierItem>
     */
    public function getPanierItems(): Collection
    {
        return $this->panierItems;
    }

    public function addPanierItem(PanierItem $panierItem): static
    {
        if (!$this->panierItems->contains($panierItem)) {
            $this->panierItems->add($panierItem);
            $panierItem->setJeu($this);
        }

        return $this;
    }

    public function removePanierItem(PanierItem $panierItem): static
    {
        if ($this->panierItems->removeElement($panierItem)) {
            // set the owning side to null (unless already changed)
            if ($panierItem->getJeu() === $this) {
                $panierItem->setJeu(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommandeLignes>
     */
    public function getCommandeLignes(): Collection
    {
        return $this->commandeLignes;
    }

    public function addCommandeLigne(CommandeLignes $commandeLigne): static
    {
        if (!$this->commandeLignes->contains($commandeLigne)) {
            $this->commandeLignes->add($commandeLigne);
            $commandeLigne->setJeu($this);
        }

        return $this;
    }

    public function removeCommandeLigne(CommandeLignes $commandeLigne): static
    {
        if ($this->commandeLignes->removeElement($commandeLigne)) {
            // set the owning side to null (unless already changed)
            if ($commandeLigne->getJeu() === $this) {
                $commandeLigne->setJeu(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, WishlistItem>
     */
    public function getWishlistItems(): Collection
    {
        return $this->wishlistItems;
    }

    public function addWishlistItem(WishlistItem $wishlistItem): static
    {
        if (!$this->wishlistItems->contains($wishlistItem)) {
            $this->wishlistItems->add($wishlistItem);
            $wishlistItem->setJeu($this);
        }

        return $this;
    }

    public function removeWishlistItem(WishlistItem $wishlistItem): static
    {
        if ($this->wishlistItems->removeElement($wishlistItem)) {
            // set the owning side to null (unless already changed)
            if ($wishlistItem->getJeu() === $this) {
                $wishlistItem->setJeu(null);
            }
        }

        return $this;
    }

    public function getMiseAJour(): ?\DateTimeImmutable
    {
        return $this->mise_a_jour;
    }

    public function setMiseAJour(?\DateTimeImmutable $mise_a_jour): static
    {
        $this->mise_a_jour = $mise_a_jour;

        return $this;
    }
}
