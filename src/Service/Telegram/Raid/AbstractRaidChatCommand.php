<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Raid\Raid;
use App\Repository\HonorRepository;
use App\Repository\RaidRepository;
use App\Repository\UserRepository;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

abstract class AbstractRaidChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        protected HonorRepository $honorRepository,
        protected RaidRepository  $raidRepository,
        protected UserRepository $userRepository,
        protected HonorService $honorService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    protected function getActiveRaid(Chat $chat): Raid
    {
        $raid = $this->raidRepository->getActiveRaid($chat);
        if ($raid === null) {
            throw new \RuntimeException('no active raid');
        }
        return $raid;
    }

    protected function getRaidKeyboard(Raid $raid): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => sprintf('⚔️ support (%d)', $raid->getSupporters()->count()),
                    'callback_data' => SupportRaidChatCommand::CALLBACK_KEYWORD,
                ],
                [
                    'text' => sprintf('🛡️ defend (%d)', $raid->getDefenders()->count()),
                    'callback_data' => DefendRaidChatCommand::CALLBACK_KEYWORD,
                ],
            ],
            [
                [
                    'text' => 'start',
                    'callback_data' => StartRaidChatCommand::CALLBACK_KEYWORD,
                ],
                [
                    'text' => 'cancel',
                    'callback_data' => CancelRaidChatCommand::CALLBACK_KEYWORD,
                ],
            ],
        ]);
    }

}