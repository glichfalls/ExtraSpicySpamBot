<?php

namespace App\Event\Effect;

use App\Entity\Chat\Chat;
use App\Entity\Item\Effect\EffectCollection;
use App\Entity\User\User;
use Symfony\Contracts\EventDispatcher\Event;

class ApplyEffectTypesEvent extends Event
{
    public const NAME = 'effect.apply';

    public function __construct(
        protected readonly EffectCollection $effects,
        protected readonly Chat $chat,
        protected readonly User $user,
        protected mixed $amount,
        protected readonly bool $isPositive = true,
    ) {
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