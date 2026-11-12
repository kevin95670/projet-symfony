<?php

namespace App\Controller;

use App\Entity\JeuComment;
use App\Entity\User;
use App\Repository\JeuRepository;
use App\Repository\JeuCommentRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class JeuCommentController extends AbstractController
{

    #[Route('/jeu/{jeuId}/{commentId}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(
        int $jeuId,
        int $commentId,
        JeuCommentRepository $commentRepository,
        EntityManagerInterface $em
    ): Response
    {
        $comment = $commentRepository->find($commentId);

        if (!$comment) {
            throw $this->createNotFoundException('Commentaire non trouvé');
        }

        // Seul l'auteur peut supprimer son message
        if ($comment->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Soft delete : le message reste dans le fil, marqué comme supprimé
        $comment->setIsDeleted(true);
        $em->flush();

        return $this->redirectVersFil($comment);
    }

    #[Route('/jeu/{jeuId}/{commentId}/update', name: 'app_comment_update', methods: ['POST'])]
    public function update(
        int $jeuId,
        int $commentId,
        JeuCommentRepository $commentRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        $comment = $commentRepository->find($commentId);

        if (!$comment) {
            throw $this->createNotFoundException('Commentaire non trouvé');
        }

        // Seul l'auteur peut modifier son message
        if ($comment->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $data = $request->request->all();
        $content = $data['comment']['contenu'] ?? '';

        if (!empty($content)) {
            $comment->setContenu($content);
            $comment->setDateModification(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->redirectVersFil($comment);
    }

    #[Route('/jeu/{jeuId}/create', name: 'app_comment_create', methods: ['POST'])]
    public function create(
        int $jeuId,
        JeuRepository $jeuRepository,
        JeuCommentRepository $commentRepository,
        EntityManagerInterface $em,
        NotificationService $notifications,
        Request $request
    ): Response
    {
        $jeu = $jeuRepository->find($jeuId);

        if (!$jeu) {
            throw $this->createNotFoundException('Jeu non trouvé');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Connecte-toi pour écrire au vendeur.');
        }

        $data = $request->request->all();
        $content = trim($data['comment']['contenu'] ?? '');

        if ($content === '') {
            return $this->redirectToRoute('app_jeu', ['id' => $jeuId, 'slug' => $jeu->getSlug()]);
        }

        $estVendeur = $user === $jeu->getVendeur();

        if ($estVendeur) {
            // Le vendeur répond dans le fil d'un acheteur précis
            $acheteur = $em->getRepository(User::class)->find($request->request->getInt('acheteurId'));

            if (!$acheteur || count($commentRepository->messagesDuFil($jeu, $acheteur)) === 0) {
                throw $this->createAccessDeniedException('Ce fil n\'existe pas pour ce jeu.');
            }
        } else {
            // L'acheteur écrit dans son propre fil : il ne voit que ses messages et ceux du vendeur
            $acheteur = $user;
        }

        $comment = new JeuComment();
        $comment->setContenu($content);
        $comment->setJeu($jeu);
        $comment->setAuteur($user);
        $comment->setAcheteur($acheteur);
        $comment->setDateCreation(new \DateTimeImmutable());
        $comment->setIsDeleted(false);

        $em->persist($comment);
        $em->flush();

        // Notification à l'autre personne du fil
        if ($estVendeur) {
            $notifications->creer(
                $acheteur,
                sprintf('Le vendeur de « %s » t\'a répondu.', $jeu->getTitre()),
                $this->generateUrl('app_jeu', ['id' => $jeu->getId(), 'slug' => $jeu->getSlug()])
            );
        } else {
            $notifications->creer(
                $jeu->getVendeur(),
                sprintf('%s t\'a écrit à propos de « %s ».', $user->getPseudo(), $jeu->getTitre()),
                $this->generateUrl('app_jeu', ['id' => $jeu->getId(), 'slug' => $jeu->getSlug(), 'fil' => $acheteur->getId()])
            );
        }

        return $this->redirectVersFil($comment);
    }

    // Revient sur la page du jeu, en gardant le fil ouvert pour le vendeur
    private function redirectVersFil(JeuComment $comment): Response
    {
        $jeu = $comment->getJeu();
        $parametres = ['id' => $jeu->getId(), 'slug' => $jeu->getSlug()];

        if ($this->getUser() === $jeu->getVendeur() && $comment->getAcheteur()) {
            $parametres['fil'] = $comment->getAcheteur()->getId();
        }

        return $this->redirectToRoute('app_jeu', $parametres);
    }
}