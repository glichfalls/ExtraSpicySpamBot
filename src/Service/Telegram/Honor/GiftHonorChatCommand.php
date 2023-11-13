<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GiftHonorChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!gift (?<amount>\d+|max)(?<abbr>[kmbtq])\s*@(?<name>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $senderHonorAmount = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        if ($matches['amount'] === 'max') {
            $amount = $senderHonorAmount;
        } else {
            $amount = NumberFormat::getIntValue($matches['amount'], $matches['abbr'] ?? null);
        }
        foreach ($this->telegramService->getUsersFromMentions($update) as $recipient) {

            if ($recipient === null) {
                $this->telegramService->replyTo($message,
                    $this->translator->trans('telegram.honor.userNotFound', ['name' => $matches['name']])
                );
                continue;
            }

            if ($senderHonorAmount < $amount) {
                $this->telegramService->replyTo(
                    $message,
                    sprintf(
                        'not enough honor to gift %s honor to %s',
                        NumberFormat::format($senderHonorAmount),
                        $recipient->getFirstName()
                    ),
                );
                continue;
            }

            $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -$amount));
            $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $recipient, $amount));
            $this->manager->flush();
            $this->telegramService->replyTo($message, sprintf(
                'you have gifted %s honor to %s',
                NumberFormat::format($amount),
                $recipient->getFirstName(),
            ));
        }
    }

    public function getHelp(): string
    {
        return '!gift <amount> ehre @<username>     gift honor to another user';
    }

    public function getDescription(): string
    {
        return 'gift honor to another user';
    }

    public function getSyntax(): string
    {
        return '!gift <amount> @<username>';
    }

}