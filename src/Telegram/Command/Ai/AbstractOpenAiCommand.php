<?php

namespace App\Telegram\Command\Ai;

use App\Service\OpenApi\OpenAiCompletionService;
use App\Service\OpenApi\OpenAiImageService;
use App\Service\Telegram\TelegramService;
use App\Telegram\Command\AbstractCommandExtension;
use Psr\Log\LoggerInterface;

abstract class AbstractOpenAiCommand extends AbstractCommandExtension
{

    public function __construct(
        protected LoggerInterface         $logger,
        protected TelegramService         $telegramService,
        protected OpenAiImageService      $openAiImageService,
        protected OpenAiCompletionService $openAiCompletionService,
    )
    {
        parent::__construct($logger, $telegramService);
    }


}