<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiController extends AbstractController
{
    private $userRepository;

    private $userPasswordHasher;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher) {
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    #[Route('/open-login-page', name: 'open_login_page', methods: ['GET'])]
    public function openLoginPage(): Response
    {
        return $this->render('login.html.twig');
    }

    #[Route('/login-user/', name: 'login', methods: ['POST'])]
    public function getTokenUser(Request $request, JWTEncoderInterface $encoder)
    {
        $parameters = [];
        parse_str($request->getContent(), $parameters);

        /**
         * @var User $user
         */
        $user = $this->userRepository->findOneBy(['email' => $parameters['email']]);

        if($this->userPasswordHasher->isPasswordValid($user, $parameters['password'])) {
            $token = $encoder->encode([
                'username' => $user->getEmail(),
                'exp' => time() + 3600, // Token expiration time
            ]);

            return $this->render('main.html.twig', ['token' => $token]);
        }

        throw new AuthenticationException('Credentials isnt valid');
    }

    #[Route('main', name: 'main', methods: ['GET'])]
    public function getMainPage()
    {
        return $this->render('main.html.twig');
    }
}