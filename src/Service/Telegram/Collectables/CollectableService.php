<?php

namespace App\Service\Telegram\Collectables;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\Collectable;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\Collectable\CollectableTransaction;
use App\Entity\User\User;
use App\Repository\CollectableItemInstanceRepository;
use App\Repository\CollectableRepository;
use Doctrine\ORM\EntityManagerInterface;

class CollectableService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private CollectableRepository $collectableRepository,
        private CollectableItemInstanceRepository $instanceRepository,
    ) {
    }

    /**
     * @return array|Collectable[]
     */
    public function getInstancableCollectables(): array
    {
        $collectables = $this->collectableRepository->findAll();
        return array_filter($collectables, fn (Collectable $collectable) => $collectable->isInstancable());
    }

    public function getInstanceById(int $id): ?CollectableItemInstance
    {
        return $this->instanceRepository->find($id);
    }

    private function transferInstance(CollectableItemInstance $instance, User $buyer, int $price): CollectableTransaction
    {
        if ($instance->getOwner() !== null) {
            throw new \RuntimeException('Instance already has an owner');
        }
        $transaction = new CollectableTransaction();
        $transaction->setInstance($instance);
        $transaction->setPrice($price);
        $transaction->setIsCompleted(true);
        $transaction->setSeller(null);
        $transaction->setBuyer($buyer);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setUpdatedAt(new \DateTime());
        $this->manager->persist($transaction);
        $this->manager->flush();
        return $transaction;
    }

    public function createCollectableInstance(Collectable $collectable, Chat $chat, ?User $user, int $price = 0): CollectableItemInstance
    {
        if ($collectable->isUnique() && $collectable->getInstances()->count() > 0) {
            throw new \RuntimeException('Collectable is unique');
        }
        $instance = new CollectableItemInstance();
        $instance->setChat($chat);
        $instance->setCollectable($collectable);
        $instance->setCreatedAt(new \DateTime());
        $instance->setUpdatedAt(new \DateTime());
        $transaction = $this->transferInstance($instance, $user, $price);
        $instance->getTransactions()->add($transaction);
        $this->manager->persist($instance);
        return $instance;
    }

}
