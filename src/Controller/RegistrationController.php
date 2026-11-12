<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, MailerInterface $mailer, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setDateCreation(new \DateTime('now'));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            try {
            $email = (new TemplatedEmail())
            ->from(new Address('contact@replay.kevinleonard.fr', 'Kevin'))
            ->to((string) $user->getEmail())
            ->subject('Bienvenue sur notre site !')
            ->htmlTemplate('registration/welcome.html.twig')
            ->context([
                'user' => $user,
            ])
            ;

            $mailer->send($email);
            } catch (\Exception $e) {
             $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email de confirmation : ' . $e->getMessage());
            }

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
