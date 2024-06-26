<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Honor\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class ApplyHonorChatCommand extends AbstractTelegramChatCommand
{
    private const MAX_HONOR_AMOUNT = 10;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    /**
     * @param Update $update
     * @param Message $message
     * @param array{} $matches
     * @return bool
     */
    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^(?<op>[+\-])\s*(?<count>\d+)\s*ehre\s*@(?<name>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    /**
     * @param Update $update
     * @param Message $message
     * @param array{ count: int, op: string, name: string } $matches
     * @return void
     */
    public function handle(Update $update, Message $message, array $matches): void
    {
        $count = (int) $matches['count'];

        $recipients = $this->telegramService->getUsersFromMentions($update);

        foreach ($recipients as $recipient) {

            if ($recipient === null) {
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.honor.userNotFound', ['name' => $matches['name']])
                );
                continue;
            }

            $this->applyHonor($message, $recipient, $matches['op'], $count);

        }
    }

    private function applyHonor(Message $message, User $recipient, string $operation, int $amount): void
    {
        if ($amount < -self::MAX_HONOR_AMOUNT || $amount > self::MAX_HONOR_AMOUNT) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.amountNotInRange', [
                'min' => -self::MAX_HONOR_AMOUNT,
                'max' => self::MAX_HONOR_AMOUNT,
            ]));
            return;
        }
        if ($operation === '+') {
            $this->honorService->addHonor($message->getChat(), $recipient, Honor::currency($amount), sender: $message->getUser());
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.receivedHonor', [
                'amount' => $amount,
                'name' => $recipient->getFirstName(),
            ]));
        } else {
            $this->honorService->removeHonor($message->getChat(), $recipient, Honor::currency($amount), sender: $message->getUser());
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.lostHonor', [
                'amount' => $amount,
                'name' => $recipient->getFirstName(),
            ]));
        }
        $this->manager->flush();

    }

    public function getHelp(): string
    {
        return '+/- <amount> ehre @<name>   give/remove honor';
    }

    public function getDescription(): string
    {
        return 'give/remove honor';
    }

    public function getSyntax(): string
    {
        return '+/- <amount> ehre @<name>';
    }

}
