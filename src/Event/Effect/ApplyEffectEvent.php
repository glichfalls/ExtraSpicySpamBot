<?php

namespace App\Event\Effect;

use App\Entity\Chat\Chat;
use App\Entity\Item\Effect\EffectType;
use App\Entity\User\User;
use Symfony\Contracts\EventDispatcher\Event;

final class ApplyEffectEvent extends Event
{
    public const NAME = 'effect.type.apply';

    public function __construct(
        protected readonly array|EffectType $effectTypes,
        protected readonly Chat $chat,
        protected readonly User $user,
        protected mixed $amount,
        protected readonly bool $isPositive = true,
    ) {
    }

    /**
     * @return EffectType[]
     */
    public function getEffectTypes(): array
    {
        return is_array($this->effectTypes) ? $this->effectTypes : [$this->effectTypes];
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAmount(): mixed
    {
        return $this->amount;
    }

    public function isPositive(): bool
    {
        return $this->isPositive;
    }

    public function setAmount(mixed $amount): void
    {
        $this->amount = $amount;
    }

}