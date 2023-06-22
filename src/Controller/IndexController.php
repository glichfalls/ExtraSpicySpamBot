<?php

namespace App\Controller;

use App\Repository\GeneratedImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(GeneratedImageRepository $repository): Response
    {
        $images = $repository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('pages/index.html.twig', [
            'images' => $images,
        ]);
    }

}