<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\RateLimitUtils;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class ApplyHonorChatCommand extends AbstractTelegramChatCommand
{

    private const RATE_LIMIT_SECONDS = 30;
    private const MAX_HONOR_AMOUNT = 10;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private HonorRepository $honorRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^(?<op>[+\-])\s*(?<count>\d+)\s*ehre\s*@(?<name>.+)$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $count = (int) $matches['count'];

        if ($matches['op'] === '-') {
            $count *= -1;
        }

        $recipients = $this->telegramService->getUsersFromMentions($update);

        foreach ($recipients as $recipient) {

            if ($recipient === null) {
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.honor.userNotFound', ['name' => $matches['name']])
                );
                continue;
            }

            $this->applyHonor($message, $recipient, $count);

        }
    }

    private function applyHonor(Message $message, User $recipient, int $amount): void
    {
        if ($amount < -self::MAX_HONOR_AMOUNT || $amount > self::MAX_HONOR_AMOUNT) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.amountNotInRange', [
                'min' => -self::MAX_HONOR_AMOUNT,
                'max' => self::MAX_HONOR_AMOUNT,
            ]));
            return;
        }

        $lastChange = $this->honorRepository->getLastChange($message->getUser(), $recipient, $message->getChat());

        if ($this->isRateLimited($lastChange->getCreatedAt())) {
            $waitTime = self::RATE_LIMIT_SECONDS - RateLimitUtils::getSecondsFrom($lastChange->getCreatedAt());
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.rateLimitExceeded', [
                'minutes' => $waitTime,
            ]));
            return;
        }

        $honor = HonorFactory::create($message->getChat(), $message->getUser(), $recipient, $amount);
        $this->manager->persist($honor);
        $this->manager->flush();
        $this->telegramService->replyTo($message, $this->translator->trans('telegram.honor.receivedHonor', [
            'amount' => $amount,
            'name' => $recipient->getFirstName(),
        ]));
    }

    public function getTimeSinceLastChange(User $sender, User $recipient, Chat $chat): ?DateInterval
    {
        $lastChange = $this->honorRepository->getLastChange($sender, $recipient, $chat);
        return $lastChange?->getCreatedAt()->diff(new \DateTime());
    }

    public function isRateLimited(?\DateTime $lastChange): bool
    {
        return RateLimitUtils::getSecondsFrom($lastChange) < self::RATE_LIMIT_SECONDS;
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
