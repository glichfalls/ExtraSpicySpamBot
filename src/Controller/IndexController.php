<?php

namespace App\Controller;

use App\Entity\OpenApi\GeneratedImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(EntityManagerInterface $manager): Response
    {
        $imageRepository = $manager->getRepository(GeneratedImage::class);
        $images = $imageRepository->findAll();
        return $this->render('pages/index.html.twig', [
            'images' => $images,
        ]);
    }

}