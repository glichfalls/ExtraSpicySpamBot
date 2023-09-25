<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Repository\CollectableItemInstanceRepository;
use App\Repository\CollectableRepository;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\Collectables\CollectableService;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCollectableTelegramCallbackQuery extends AbstractTelegramCallbackQuery
{

    public const SUCCESS = 1;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        protected HonorRepository $honorRepository,
        protected CollectableService $collectableService,
        protected CollectableRepository $collectableRepository,
        protected CollectableItemInstanceRepository $collectableItemInstanceRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

}
