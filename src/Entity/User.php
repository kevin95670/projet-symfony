<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_PSEUDO', fields: ['pseudo'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(length: 70)]
    private ?string $nom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $pseudo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_naissance = null;

    /**
     * @var Collection<int, Jeu>
     */
    #[ORM\OneToMany(targetEntity: Jeu::class, mappedBy: 'vendeur')]
    private Collection $jeux;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Panier $panier = null;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user')]
    private Collection $commandes;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    /**
     * @var Collection<int, Avis> Avis que cet utilisateur a écrits
     */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'auteur')]
    private Collection $avisEnvoyes;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'vendeur')]
    private Collection $avisRecus;

    /**
     * @var Collection<int, WishlistItem>
     */
    #[ORM\OneToMany(targetEntity: WishlistItem::class, mappedBy: 'user')]
    private Collection $wishlistItems;

    /**
     * @var Collection<int, JeuComment>
     */
    #[ORM\OneToMany(targetEntity: JeuComment::class, mappedBy: 'auteur')]
    private Collection $jeuComments;

    // Identifiant du client chez Stripe, créé au premier achat
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    // Dernière adresse de livraison utilisée (nom, ligne1, ligne2, code_postal, ville, pays)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adresseLivraison = null;

    public function __construct()
    {
        $this->jeux = new ArrayCollection();
        $this->commandes = new ArrayCollection();
        $this->avisEnvoyes = new ArrayCollection();
        $this->avisRecus = new ArrayCollection();
        $this->wishlistItems = new ArrayCollection();
        $this->jeuComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTime $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    /**
     * @return Collection<int, Jeu>
     */
    public function getJeux(): Collection
    {
        return $this->jeux;
    }

    public function addJeux(Jeu $jeux): static
    {
        if (!$this->jeux->contains($jeux)) {
            $this->jeux->add($jeux);
            $jeux->setVendeur($this);
        }

        return $this;
    }

    public function removeJeux(Jeu $jeux): static
    {
        if ($this->jeux->removeElement($jeux)) {
            // set the owning side to null (unless already changed)
            if ($jeux->getVendeur() === $this) {
                $jeux->setVendeur(null);
            }
        }

        return $this;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(Panier $panier): static
    {
        // set the owning side of the relation if necessary
        if ($panier->getUser() !== $this) {
            $panier->setUser($this);
        }

        $this->panier = $panier;

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setUser($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getUser() === $this) {
                $commande->setUser(null);
            }
        }

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvisEnvoyes(): Collection
    {
        return $this->avisEnvoyes;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvisRecus(): Collection
    {
        return $this->avisRecus;
    }

    public function addAvisEnvoye(Avis $avisEnvoye): static
    {
        if (!$this->avisEnvoyes->contains($avisEnvoye)) {
            $this->avisEnvoyes->add($avisEnvoye);
            $avisEnvoye->setAuteur($this);
        }

        return $this;
    }

    public function removeAvisEnvoye(Avis $avisEnvoye): static
    {
        if ($this->avisEnvoyes->removeElement($avisEnvoye)) {
            // set the owning side to null (unless already changed)
            if ($avisEnvoye->getAuteur() === $this) {
                $avisEnvoye->setAuteur(null);
            }
        }

        return $this;
    }

    public function addAvisRecu(Avis $avisRecu): static
    {
        if (!$this->avisRecus->contains($avisRecu)) {
            $this->avisRecus->add($avisRecu);
            $avisRecu->setVendeur($this);
        }

        return $this;
    }

    public function removeAvisRecu(Avis $avisRecu): static
    {
        if ($this->avisRecus->removeElement($avisRecu)) {
            // set the owning side to null (unless already changed)
            if ($avisRecu->getVendeur() === $this) {
                $avisRecu->setVendeur(null);
            }
        }

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
            $wishlistItem->setUser($this);
        }

        return $this;
    }

    public function removeWishlistItem(WishlistItem $wishlistItem): static
    {
        if ($this->wishlistItems->removeElement($wishlistItem)) {
            // set the owning side to null (unless already changed)
            if ($wishlistItem->getUser() === $this) {
                $wishlistItem->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JeuComment>
     */
    public function getJeuComments(): Collection
    {
        return $this->jeuComments;
    }

    public function addJeuComment(JeuComment $jeuComment): static
    {
        if (!$this->jeuComments->contains($jeuComment)) {
            $this->jeuComments->add($jeuComment);
            $jeuComment->setAuteur($this);
        }

        return $this;
    }

    public function removeJeuComment(JeuComment $jeuComment): static
    {
        if ($this->jeuComments->removeElement($jeuComment)) {
            // set the owning side to null (unless already changed)
            if ($jeuComment->getAuteur() === $this) {
                $jeuComment->setAuteur(null);
            }
        }

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getAdresseLivraison(): ?array
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?array $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }
}