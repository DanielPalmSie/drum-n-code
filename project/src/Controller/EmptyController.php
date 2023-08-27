<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmptyController extends AbstractController
{
    #[Route('/api/empty', name: 'empty', methods: ['GET'])]
    public function index(): Response
    {
        // You can modify this method or remove it based on your needs.
        return $this->json('test');
    }
}