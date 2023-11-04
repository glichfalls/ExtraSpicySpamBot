<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Repository\ItemInstanceRepository;
use App\Repository\ItemRepository;
use App\Repository\HonorRepository;
use App\Service\HonorService;
use App\Service\Items\CollectableService;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractItemTelegramCallbackQuery extends AbstractTelegramCallbackQuery
{

    public const SUCCESS = 1;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        protected HonorService $honorService,
        protected HonorRepository $honorRepository,
        protected CollectableService $collectableService,
        protected ItemService $itemService,
        protected ItemTradeService $itemTradeService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

}
