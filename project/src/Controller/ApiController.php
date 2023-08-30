<?php

namespace App\Controller;

use App\Services\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    private $tokenService;

    public function __construct( TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('/open-login-page', name: 'open_login_page', methods: ['GET'])]
    public function openLoginPage(): Response
    {
        return $this->render('login.html.twig');
    }

    #[Route('/login-user/', name: 'login', methods: ['POST'])]
    public function getTokenUser(Request $request)
    {
        $parameters = [];
        parse_str($request->getContent(), $parameters);

        $token = $this->tokenService->getTokenForUser($parameters['email'], $parameters['password']);

        return $this->render('main.html.twig', ['token' => $token]);
    }

    #[Route('main', name: 'main', methods: ['GET'])]
    public function getMainPage()
    {
        return $this->render('main.html.twig');
    }
}