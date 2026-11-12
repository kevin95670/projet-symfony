<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\SignalementJeu;
use App\Entity\User;
use App\Enum\JeuStatut;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SignalementController extends AbstractController
{
    // Motifs proposés à l'utilisateur : clé enregistrée => libellé affiché
    public const MOTIFS = [
        'contenu_trompeur' => 'Annonce trompeuse',
        'photo_inappropriee' => 'Photo inappropriée',
        'contrefacon' => 'Jeu contrefait ou copie illégale',
        'autre' => 'Autre',
    ];

    // Un signalement masque l'annonce immédiatement (si elle est en vente), en attendant la vérification
    #[Route('/jeu/{id}/signaler', name: 'app_jeu_signaler', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function signaler(Jeu $jeu, Request $request, EntityManagerInterface $em, NotificationService $notifications): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        // Retour vers la liste : la fiche d'une annonce masquée n'est plus accessible à l'acheteur
        $retour = $this->redirectToRoute('app_jeux_liste');

        if (!$this->isCsrfTokenValid('signaler-' . $jeu->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Session expirée, réessaie.');
            return $retour;
        }

        if ($jeu->getVendeur() === $user) {
            $this->addFlash('error', 'Tu ne peux pas signaler ta propre annonce.');
            return $retour;
        }

        $motif = $request->request->getString('motif');
        if (!array_key_exists($motif, self::MOTIFS)) {
            $this->addFlash('error', 'Choisis un motif valide.');
            return $retour;
        }

        $dejaSignale = $em->getRepository(SignalementJeu::class)->findOneBy(['jeu' => $jeu, 'auteur' => $user]);
        if ($dejaSignale) {
            $this->addFlash('error', 'Tu as déjà signalé cette annonce.');
            return $retour;
        }

        $signalement = new SignalementJeu();
        $signalement->setJeu($jeu);
        $signalement->setAuteur($user);
        $signalement->setMotif($motif);
        $signalement->setCommentaire(mb_substr(trim($request->request->getString('commentaire')), 0, 500) ?: null);
        $signalement->setDateCreation(new \DateTimeImmutable());

        // Masquage immédiat, réversible par l'admin (une seule notification au vendeur, même si plusieurs signalements)
        $nouveauMasquage = false;
        if ($jeu->getStatut() === JeuStatut::EN_VENTE && !$jeu->isMasque()) {
            $jeu->setMasque(true);
            $nouveauMasquage = true;
        }

        $em->persist($signalement);
        $em->flush();

        if ($nouveauMasquage) {
            $this->notifierVendeur($notifications, $jeu, sprintf(
                'Ton annonce « %s » a été suspendue. Motif : %s. Elle est en cours de vérification par l\'équipe ; tu seras prévenu de la décision.',
                $jeu->getTitre(),
                self::MOTIFS[$motif]
            ));
        }

        $this->addFlash('success', 'Merci, le signalement a été envoyé. L\'annonce est masquée en attendant la vérification.');

        return $retour;
    }

    // Espace admin : tous les signalements, groupés par annonce
    #[Route('/admin/signalements', name: 'app_admin_signalements')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EntityManagerInterface $em): Response
    {
        $signalements = $em->getRepository(SignalementJeu::class)->findBy([], ['dateCreation' => 'DESC']);

        $parJeu = [];
        foreach ($signalements as $signalement) {
            $jeuId = $signalement->getJeu()->getId();
            $parJeu[$jeuId]['jeu'] = $signalement->getJeu();
            $parJeu[$jeuId]['signalements'][] = $signalement;
        }

        return $this->render('admin/signalements.html.twig', [
            'parJeu' => $parJeu,
            'motifs' => self::MOTIFS,
        ]);
    }

    // Rétablit l'annonce et marque tous ses signalements comme traités
    #[Route('/admin/jeux/{id}/restaurer', name: 'app_admin_jeu_restaurer', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restaurer(Jeu $jeu, Request $request, EntityManagerInterface $em, NotificationService $notifications): Response
    {
        if (!$this->isCsrfTokenValid('admin-jeu-' . $jeu->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Session expirée, réessaie.');
            return $this->redirectToRoute('app_admin_signalements');
        }

        $etaitMasque = $jeu->isMasque();
        $jeu->setMasque(false);

        foreach ($em->getRepository(SignalementJeu::class)->findBy(['jeu' => $jeu]) as $signalement) {
            $signalement->setTraite(true);
        }

        $em->flush();

        if ($etaitMasque) {
            $this->notifierVendeur($notifications, $jeu, sprintf(
                'Ton annonce « %s » a été rétablie après vérification. Elle est de nouveau visible.',
                $jeu->getTitre()
            ));
        }

        $this->addFlash('success', 'Annonce rétablie.');

        return $this->redirectToRoute('app_admin_signalements');
    }

    // Masque l'annonce à la main (sans signalement)
    #[Route('/admin/jeux/{id}/masquer', name: 'app_admin_jeu_masquer', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function masquer(Jeu $jeu, Request $request, EntityManagerInterface $em, NotificationService $notifications): Response
    {
        if (!$this->isCsrfTokenValid('admin-jeu-' . $jeu->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Session expirée, réessaie.');
            return $this->redirectToRoute('app_admin_signalements');
        }

        // La raison est obligatoire : le vendeur la reçoit dans sa notification
        $raison = mb_substr(trim($request->request->getString('raison')), 0, 255);

        if ($raison === '') {
            $this->addFlash('error', 'Indique une raison pour masquer l\'annonce.');
        } elseif ($jeu->getStatut() === JeuStatut::EN_VENTE) {
            $jeu->setMasque(true);
            $em->flush();

            $this->notifierVendeur($notifications, $jeu, sprintf(
                'Ton annonce « %s » a été suspendue par l\'équipe. Raison : %s.',
                $jeu->getTitre(),
                $raison
            ));

            $this->addFlash('success', 'Annonce masquée. Le vendeur a été prévenu.');
        }

        return $this->redirectToRoute('app_admin_signalements');
    }

    // Envoie une notification au vendeur, avec un lien vers son annonce
    private function notifierVendeur(NotificationService $notifications, Jeu $jeu, string $message): void
    {
        $notifications->creer(
            $jeu->getVendeur(),
            $message,
            $this->generateUrl('app_jeu', ['id' => $jeu->getId(), 'slug' => $jeu->getSlug()])
        );
    }
}