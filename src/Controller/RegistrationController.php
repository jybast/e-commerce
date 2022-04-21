<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JWTService;
use App\Service\SendMailService;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mailer, JWTService $jwt): Response
    {
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // On génère le JWT de l'utilisateur
            // on crée le header
            $header = [
                'alg' => 'HS256',
                'typ' => 'JWT'
            ];

            $payload = [
                'id' => $user->getId(),
            ];

            // on généère le token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'), 10800);




            // Envoi du mail avec le token

            $mailer->send(
                'no-reply@monsite.fr',
                $user->getEmail(),
                'Activation de votre compte',
                'register',
                [
                    'user' => $user,
                    'token' => $token,

                ]
            );

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        // on vérifie si le token est valide, n'a pas expiré et est bien formé
        if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->isValidSignature($token, $this->getParameter('app.jwtsecret'))) {
            // récupère le payload du token
            $payload = $jwt->getPayload($token);
            // récupère l'utilisateur
            $user = $userRepository->find($payload['user_id']);
            // on vérifie si utilisateur existe et n'a pas encore activé son compte
            if ($user && !$user->getIsVerified()) {
                $user->setIsVerified(true);
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Utilisateur activé avec succès');
                return $this->redirectToRoute('profile_index');
            }
        }
        // Ici un problème avec le token
        $this->addFlash('danger', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }

    // renvoi de la vérification
    #[Route('/renvoiverif', name: 'resend_verif')]
    public function resendVerif(
        JWTService $jwt,
        SendMailService $mailer,
        UserRepository $userRepository,
    ): Response {
        // utilisateur connecté
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour renvoyer le mail de vérification');
            return $this->redirectToRoute('app_login');
        }

        // on vérifie si l'utilisateur a déjà activé son compte
        if ($user->getIsVerified()) {
            $this->addFlash('warning', 'Votre compte est déjà activé');
            return $this->redirectToRoute('profile_index');
        }

        // On génère le JWT de l'utilisateur
        // on crée le header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        // on crée le payload
        $payload = [
            'id' => $user->getId(),
        ];
        // on généère le token
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'), 10800);

        // Envoi du mail avec le token
        $mailer->send(
            'no-reply@monsite.fr',
            $user->getEmail(),
            'Activation de votre compte',
            'register',
            [
                'user' => $user,
                'token' => $token,
            ]
        );

        $this->addFlash('success', 'Email de vérification envoyé !');
        return $this->redirectToRoute('profile_index');
    }
}
