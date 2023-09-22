<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTelegramHonorChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        protected HonorRepository $honorRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    protected function getCurrentHonorAmount(Chat $chat, User $user): int
    {
        return $this->honorRepository->getHonorCount($user, $chat);
    }

    protected function addHonor(Chat $chat, User $recipient, int $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, abs($amount)));
    }

    protected function removeHonor(Chat $chat, User $recipient, int $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, -abs($amount)));
    }

}
