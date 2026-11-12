<?php

namespace App\Service;

use App\Entity\Jeu;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// Envoie les alertes de prix et de retour en stock, uniquement sous forme de notifications dans le site
class AlerteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationService $notifications,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    // Appelé après la modification d'un jeu : prévient les acheteurs intéressés si le prix a changé (baisse ou hausse)
    public function prixModifie(Jeu $jeu, ?string $ancienPrix): void
    {
        if ($ancienPrix === null || (float) $ancienPrix === (float) $jeu->getPrix()) {
            return;
        }

        $ancien = (float) $ancienPrix;
        $nouveau = (float) $jeu->getPrix();
        $verbe = $nouveau < $ancien ? 'a baissé' : 'a augmenté';

        $message = sprintf(
            'Le prix de « %s » %s : %s € → %s €.',
            $jeu->getTitre(),
            $verbe,
            number_format($ancien, 2, ',', ' '),
            number_format($nouveau, 2, ',', ' ')
        );
        $lien = $this->lienJeu($jeu);

        foreach ($this->utilisateursInteresses([$jeu]) as $user) {
            $this->notifier($user, $message, $lien);
        }
    }

    // Appelé après la mise en vente d'un nouveau jeu : prévient les acheteurs qui ont le même titre
    // sur la même plateforme dans leur wishlist ou leur panier, quel que soit le vendeur
    public function miseEnVenteNouvelle(Jeu $jeu): void
    {
        $memesJeux = $this->em->createQuery(
            'SELECT j FROM App\Entity\Jeu j
             WHERE LOWER(j.titre) = :titre AND j.plateforme = :plateforme AND j.id <> :id'
        )
            ->setParameter('titre', mb_strtolower($jeu->getTitre()))
            ->setParameter('plateforme', $jeu->getPlateforme())
            ->setParameter('id', $jeu->getId())
            ->getResult();

        if (count($memesJeux) === 0) {
            return;
        }

        $message = sprintf(
            '« %s » (%s) est de nouveau en vente.',
            $jeu->getTitre(),
            $jeu->getPlateforme()->value
        );
        $lien = $this->lienJeu($jeu);

        foreach ($this->utilisateursInteresses($memesJeux) as $user) {
            // Le vendeur de la nouvelle annonce n'est pas notifié de sa propre annonce
            if ($user === $jeu->getVendeur()) {
                continue;
            }
            $this->notifier($user, $message, $lien);
        }
    }

    /**
     * Acheteurs qui ont au moins un de ces jeux dans leur wishlist ou leur panier.
     * Un utilisateur n'apparaît qu'une fois, même s'il a le jeu à plusieurs endroits.
     *
     * @param Jeu[] $jeux
     * @return User[]
     */
    private function utilisateursInteresses(array $jeux): array
    {
        $users = [];

        foreach ($jeux as $jeu) {
            foreach ($jeu->getWishlistItems() as $item) {
                $user = $item->getUser();
                $users[$user->getId()] = $user;
            }

            foreach ($jeu->getPanierItems() as $item) {
                $user = $item->getPanier()->getUser();
                $users[$user->getId()] = $user;
            }
        }

        return $users;
    }

    // Envoie la notification, sauf si une notification non lue existe déjà pour ce jeu :
    // ainsi l'acheteur ne reçoit pas un message à chaque modification de l'annonce
    private function notifier(User $user, string $message, string $lien): void
    {
        $dejaNotifie = (int) $this->em->createQuery(
            'SELECT COUNT(n.id) FROM App\Entity\Notification n
             WHERE n.user = :user AND n.lien = :lien AND n.lue = false'
        )
            ->setParameter('user', $user)
            ->setParameter('lien', $lien)
            ->getSingleScalarResult();

        if ($dejaNotifie > 0) {
            return;
        }

        $this->notifications->creer($user, $message, $lien);
    }

    private function lienJeu(Jeu $jeu): string
    {
        return $this->urlGenerator->generate('app_jeu', [
            'slug' => $jeu->getSlug(),
            'id' => $jeu->getId(),
        ]);
    }
}