<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Service\HonorService;
use App\Service\Items\ItemEffectService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GambleHonorChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
        private readonly DrawRepository $drawRepository,
        private readonly ItemEffectService $itemEffectService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(gamble|g)\s(?<amount>\d+|max)(?<abbr>[km])?$/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $currentHonor = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        if ($matches['amount'] === 'max') {
            $amount = $currentHonor;
        } else {
            $amount = NumberFormat::getIntValue($matches['amount'], $matches['abbr'] ?? null);
        }
        $this->logger->info(sprintf('GAMBLE %s honor', $amount));
        if ($amount < 0) {
            $this->logger->info('GAMBLE failed, negative honor');
            $this->telegramService->replyTo($message, 'you cannot gamble negative Ehre');
            return;
        }
        if ($currentHonor < $amount) {
            $this->logger->info('GAMBLE failed, not enough honor');
            $this->telegramService->replyTo($message, 'not enough Ehre');
        } else {
            $this->logger->info(sprintf('GAMBLE %s start', $message->getUser()->getName()));
            $effects = $this->itemEffectService->getEffectsByUserAndType($message->getUser(), $message->getChat(), [
                EffectType::GAMBLE_LUCK,
                EffectType::LUCK,
            ]);
            $chance = $this->getChance($effects);
            $buffMessage = sprintf('win chance: %s%%', $chance);
            if ($this->gamble($chance)) {
                $this->logger->info(sprintf('GAMBLE %s won %s honor', $message->getUser()->getName(), $amount));
                $this->honorService->addHonor($message->getChat(), $message->getUser(), $amount);
                $this->manager->flush();
                $this->telegramService->replyTo(
                    $message,
                    sprintf('you have won %s Ehre (%s)', NumberFormat::format($amount), $buffMessage)
                );
            } else {
                $this->logger->info(sprintf('GAMBLE %s lost %s honor', $message->getUser()->getName(), $amount));
                $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
                $jackpotDrawAmount = (int) floor(abs($amount) * 0.9);
                if ($draw !== null && $draw->getGamblingLosses() < PHP_INT_MAX - $jackpotDrawAmount) {
                    $draw->setGamblingLosses($draw->getGamblingLosses() + $jackpotDrawAmount);
                }
                $jackpot = $this->honorService->getSlotMachineJackpot($message->getChat());
                $slotMachineJackpotAmount = (int) floor(abs($amount) * 0.1);
                if ($jackpot->getAmount() < PHP_INT_MAX - $slotMachineJackpotAmount) {
                    $jackpot->setAmount($jackpot->getAmount() + $slotMachineJackpotAmount);
                }
                $this->honorService->removeHonor($message->getChat(), $message->getUser(), $amount);
                $this->manager->flush();
                $this->telegramService->replyTo(
                    $message,
                    sprintf('you have lost %s Ehre (%s)', NumberFormat::format($amount), $buffMessage),
                );
            }
            if ($message->getChat()->getConfig()->isDebugEnabled()) {
                $effectList = [];
                $value = 50;
                foreach ($effects->getValues() as $effect) {
                    $magnitude = round($effect->getMagnitude(), 2);
                    $previousValue = round($value, 2);
                    $value = $effect->apply($value);
                    $displayValue = round($value, 2);
                    $effectList[] = <<<TEXT
                    <strong>{$effect->getType()->value}</strong>
                    {$previousValue} {$effect->getOperator()} {$magnitude} = {$displayValue}
                    TEXT;
                }
                $effectList[] = sprintf('<strong>Total:</strong> %s (%s%%)', round($value, 2), $chance);
                $this->telegramService->replyTo($message, implode(PHP_EOL, $effectList), parseMode: 'HTML');
            }
        }
    }

    private function getChance(EffectCollection $effects): int
    {
        try {
            $chance = $effects->apply(50);
            $this->logger->info(sprintf('gamble luck effects: %s', $effects->count()));
            if ($chance < 30) {
                return 30;
            }
            if ($chance > 70) {
                return 70;
            }
            return (int) ceil($chance);
        } catch (\Exception $exception) {
            $this->logger->info('failed to apply gamble luck effects', [
                'exception' => $exception,
            ]);
            return 50;
        }
    }

    private function gamble(int $chance): bool
    {
        return Random::getPercentChance((int) min($chance, 100));
    }

    public function getSyntax(): string
    {
        return '!gamble [amount] | !g [amount] | !gamble max | !g max';
    }

    public function getDescription(): string
    {
        return 'gamble Ehre (50% win chance)';
    }

}
