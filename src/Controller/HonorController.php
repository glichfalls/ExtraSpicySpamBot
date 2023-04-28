<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use TelegramBot\Api\BotApi;

class HonorController extends AbstractController
{

    public function __construct(private BotApi $bot)
    {
    }

    public function addHonor(): Response
    {

    }

}