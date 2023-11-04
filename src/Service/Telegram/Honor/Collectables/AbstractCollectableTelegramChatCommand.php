<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Repository\ItemInstanceRepository;
use App\Repository\ItemRepository;
use App\Repository\HonorRepository;
use App\Service\Items\CollectableService;
use App\Service\Items\ItemService;
use App\Service\Telegram\Honor\AbstractTelegramHonorChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCollectableTelegramChatCommand extends AbstractTelegramHonorChatCommand
{

    public const SUCCESS = 1;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        HonorRepository $honorRepository,
        protected CollectableService $collectableService,
        protected ItemRepository $collectableRepository,
        protected ItemInstanceRepository $collectableItemInstanceRepository,
        protected ItemService $itemService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService, $honorRepository);
    }

}
