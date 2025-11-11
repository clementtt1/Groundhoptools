<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirect('/');
        }
    
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
    
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }


    #[Route('/register', name: 'app_register', methods: ['POST'])]
public function register(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher
): Response {
    $username = $request->request->get('username');
    $email = $request->request->get('email');
    $password = $request->request->get('password');

    if (!$username || !$email || !$password) {
        $this->addFlash('error', 'Tous les champs sont obligatoires.');
        return $this->redirectToRoute('app_login');
    }

    // Vérifier si le username existe déjà
    $existingUserByUsername = $em->getRepository(User::class)->findOneBy(['username' => $username]);
    if ($existingUserByUsername) {
        $this->addFlash('error', 'Ce nom d’utilisateur est déjà utilisé.');
        return $this->redirectToRoute('app_login');
    }

    // Vérifier si l'email existe déjà
    $existingUserByEmail = $em->getRepository(User::class)->findOneBy(['email' => $email]);
    if ($existingUserByEmail) {
        $this->addFlash('error', 'Cet email est déjà utilisé.');
        return $this->redirectToRoute('app_login');
    }

    // Création du nouvel utilisateur
    $user = new User();
    $user->setUsername($username);
    $user->setEmail($email);
    $user->setPassword($hasher->hashPassword($user, $password));
    $user->setIsActive(true);

    $em->persist($user);
    $em->flush();

    $this->addFlash('success', 'Votre compte a bien été créé. Vous pouvez vous connecter !');
    return $this->redirectToRoute('app_login');
}


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
