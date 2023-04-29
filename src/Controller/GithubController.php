<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GithubController
{

    #[Route('/github/webhook', methods: ['POST'])]
    public function webhook(Request $request): void
    {
        // run cache clear
        exec('php bin/console cache:clear');
    }

}