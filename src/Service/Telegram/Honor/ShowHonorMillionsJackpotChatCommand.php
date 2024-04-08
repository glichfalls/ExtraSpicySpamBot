<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\HonorMillions\Draw\Draw;
use App\Entity\Message\Message;
use App\Repository\DrawRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

final class ShowHonorMillionsJackpotChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly DrawRepository $drawRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!jackpot/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
        if ($draw === null) {
            $this->telegramService->replyTo($message, 'there is no draw for this chat');
            return;
        }
        $jackpot = $draw->getJackpot();
        $this->telegramService->replyTo($message, sprintf(
            'the jackpot is %s Ehre (+%s today). %s%% chance someone wins today',
            NumberFormat::money($jackpot),
            NumberFormat::money($draw->getGamblingLosses()),
            $this->getTotalUniqueNumbers($draw),
        ));
    }

    private function getTotalUniqueNumbers(Draw $draw): int
    {
        $numbers = [];
        foreach ($draw->getTickets() as $ticket) {
            $numbers = array_merge($numbers, $ticket->getNumbers());
        }
        return count(array_unique($numbers));
    }

    public function getSyntax(): string
    {
        return '!jackpot';
    }

    public function getDescription(): string
    {
        return 'show the jackpot';
    }

}
