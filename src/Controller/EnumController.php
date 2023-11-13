<?php

namespace App\Controller;

use App\Entity\Item\Attribute\ItemRarity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EnumController extends AbstractController
{

    #[Route('/enum/rarity')]
    public function rarity(): Response
    {
        return $this->json(ItemRarity::cases());
    }

    #[Route('/enum/attributes')]
    public function attributes(): Response
    {
        return $this->json(ItemRarity::cases());
    }

}
