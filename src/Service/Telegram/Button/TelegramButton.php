<?php

namespace App\Service\Telegram\Button;

class TelegramButton
{

    private string $callbackData = '';

    public function __construct(private readonly string $name, string $callbackData = '')
    {
        $this->setCallbackData($callbackData);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallbackData(): string
    {
        return $this->callbackData;
    }

    public function setCallbackData(string $callbackData): void
    {
        if (strlen($callbackData) > 64) {
            throw new \InvalidArgumentException('Callback data must be 64 characters or less.');
        }
        $this->callbackData = $callbackData;
    }

}