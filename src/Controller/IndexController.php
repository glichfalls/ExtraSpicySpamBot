<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(LoggerInterface $logger): Response
    {
        $logger->info('test');
        return $this->render('pages/index.html.twig');
    }

}