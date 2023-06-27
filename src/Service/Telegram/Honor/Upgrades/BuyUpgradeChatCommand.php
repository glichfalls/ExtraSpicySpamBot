<?php

namespace App\Service\Telegram\Honor\Upgrades;

use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\Upgrade\UpgradeFactory;
use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Repository\HonorUpgradeRepository;
use App\Repository\HonorUpgradeTypeRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class BuyUpgradeChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private HonorUpgradeTypeRepository $upgradeTypeRepository,
        private HonorUpgradeRepository $upgradeRepository,
        private HonorRepository $honorRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!buy upgrade (?<code>.+)/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $code = $matches['code'];
        $upgradeType = $this->upgradeTypeRepository->getByCode($code);
        if ($upgradeType === null) {
            $this->telegramService->replyTo($message, sprintf('Upgrade %s not found', $code));
            return;
        }
        $existingUpgrade = $this->upgradeRepository->getUpgradeByChatAndUser($message->getChat(), $message->getUser(), $upgradeType);
        if ($existingUpgrade !== null) {
            $this->telegramService->replyTo($message, sprintf('You already have %s', $upgradeType->getName()));
            return;
        }
        $honor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($honor < $upgradeType->getPrice()) {
            $this->telegramService->replyTo($message, sprintf('You dont have enough honor to buy %s', $upgradeType->getName()));
            return;
        }
        $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -$upgradeType->getPrice()));
        $upgrade = UpgradeFactory::create($message->getChat(), $message->getUser(), $upgradeType);
        $this->manager->persist($upgrade);
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf('You bought %s', $upgradeType->getName()));
    }

    public function getHelp(): string
    {
        return '!buy upgrade <code>     Buy an upgrade';
    }

}