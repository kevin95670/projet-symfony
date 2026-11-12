<?php

namespace App\Controller;

use App\Entity\CommandeLignes;
use App\Enum\StatutLigne;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class VenteController extends AbstractController
{
    // Liste des jeux vendus par le vendeur connecté, à expédier
    #[Route('/expeditions', name: 'app_ventes_expeditions')]
    #[IsGranted('ROLE_USER')]
    public function expeditions(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Seulement les lignes dont le jeu appartient à ce vendeur
        $lignes = $em->getRepository(CommandeLignes::class)
            ->createQueryBuilder('l')
            ->join('l.jeu', 'j')
            ->andWhere('j.vendeur = :vendeur')
            ->setParameter('vendeur', $user)
            ->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('vente/expeditions.html.twig', [
            'lignes' => $lignes,
        ]);
    }

    // Le vendeur marque le jeu comme expédié (numéro de suivi obligatoire)
    #[Route('/ventes/ligne/{id}/avancer', name: 'app_vente_ligne_avancer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function avancer(
        CommandeLignes $ligne,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        NotificationService $notifications,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Sécurité : seul le vendeur du jeu peut modifier la ligne
        if ($ligne->getJeu()->getVendeur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Ce jeu ne vous appartient pas.');
        }

        if (!$this->isCsrfTokenValid('ligne-' . $ligne->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton invalide.');
        }

        // Le vendeur ne fait que l'expédition : la réception est confirmée par l'acheteur
        if ($ligne->getStatut() !== StatutLigne::EN_PREPARATION) {
            $this->addFlash('error', 'Ce jeu a déjà été expédié. La réception est confirmée par l\'acheteur.');
            return $this->redirectToRoute('app_ventes_expeditions');
        }

        $numero = trim($request->request->getString('numero_suivi'));

        if ($numero === '') {
            $this->addFlash('error', 'Saisis le numéro de suivi avant de marquer le colis comme expédié.');
            return $this->redirectToRoute('app_ventes_expeditions');
        }

        $ligne->setNumeroSuivi($numero);
        $ligne->setStatut(StatutLigne::EXPEDIEE);
        $em->flush();

        $acheteur = $ligne->getCommande()->getUser();

        // Notification dans le site pour l'acheteur
        $notifications->creer(
            $acheteur,
            sprintf('Ton jeu « %s » a été expédié.', $ligne->getJeu()->getTitre()),
            $this->generateUrl('app_commande', ['id' => $ligne->getCommande()->getId()])
        );

        // Email à l'acheteur quand le colis part
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('contact@replay.kevinleonard.fr', 'Kevin'))
                ->to((string) $acheteur->getEmail())
                ->subject('Ton jeu a été expédié !')
                ->htmlTemplate('commande/expedie.html.twig')
                ->context([
                    'user' => $acheteur,
                    'ligne' => $ligne,
                    'commande' => $ligne->getCommande(),
                ]);

            $mailer->send($email);
        } catch (\Exception $e) {
            // Le statut est déjà enregistré : un échec d'email ne doit pas l'annuler
        }

        $this->addFlash('success', 'Jeu marqué comme expédié.');

        return $this->redirectToRoute('app_ventes_expeditions');
    }
}