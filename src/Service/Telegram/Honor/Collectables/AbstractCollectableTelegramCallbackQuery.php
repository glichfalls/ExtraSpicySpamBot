<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableAuction;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\User\User;
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

    /**
     * @param Chat $chat
     * @param User $user
     * @return CollectableItemInstance[]
     */
    protected function getCollection(Chat $chat, User $user): array
    {
        return $this->collectableItemInstanceRepository->getCurrentCollectionByChatAndUser($chat, $user);
    }

    protected function getAuction(CollectableItemInstance $instance): CollectableAuction
    {
        $auction = $this->collectableItemInstanceRepository->findOneBy([
            'instance' => $instance,
            'active' => true,
        ]);
        if ($auction !== null) {
            return $auction;
        }
        if (!$instance->getCollectable()->isTradeable()) {
            throw new \RuntimeException('Collectable is not tradeable.');
        }
        $auction = new CollectableAuction();
        $auction->setInstance($instance);
        $auction->setSeller($instance->getOwner());
        $auction->setHighestBidder(null);
        $auction->setHighestBid(0);
        $auction->setActive(true);
        $auction->setCreatedAt(new \DateTime());
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->persist($auction);
        $this->manager->flush();
        return $auction;
    }

}
