<?php

namespace App\Controller;

use App\Entity\JeuComment;
use App\Repository\JeuRepository;
use App\Repository\JeuCommentRepository;
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
        
        // Vérifier que le commentaire existe
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire non trouvé');
        }
        
        // Vérifier que c'est l'auteur
        if ($comment->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Soft delete
        $comment->setIsDeleted(true);
        $em->flush();

        return $this->redirectToRoute('app_jeu', [
            'id' => $jeuId,
            'slug' => $comment->getJeu()->getSlug()
        ]);
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
        
        // Vérifier que le commentaire existe
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire non trouvé');
        }
        
        // Vérifier que c'est l'auteur
        if ($comment->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Mettre à jour le contenu du commentaire
        $data = $request->request->all();
        $content = $data['comment']['contenu'] ?? '';

        if (!empty($content)) {
            $comment->setContenu($content);
            $comment->setDateModification(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->redirectToRoute('app_jeu', [
            'id' => $jeuId,
            'slug' => $comment->getJeu()->getSlug()
        ]);
    }

    #[Route('/jeu/{jeuId}/create', name: 'app_comment_create', methods: ['POST'])]
    public function create(
        int $jeuId, 
        JeuRepository $jeuRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        $jeu = $jeuRepository->find($jeuId);
        
        // Vérifier que le jeu existe
        if (!$jeu) {
            throw $this->createNotFoundException('Jeu non trouvé');
        }

        // Créer un nouveau commentaire
        $comment = new JeuComment();
        $data = $request->request->all();
        $content = $data['comment']['contenu'] ?? '';
        
        if (!empty($content)) {
            $comment->setContenu($content);
            $comment->setJeu($jeu);
            $comment->setAuteur($this->getUser());
            $comment->setDateCreation(new \DateTimeImmutable());
            $comment->setIsDeleted(false);
            
            $em->persist($comment);
            $em->flush();
        }

        return $this->redirectToRoute('app_jeu', [
            'id' => $jeuId,
            'slug' => $jeu->getSlug()
        ]);
    }
}
