<?php

namespace App\Service\Telegram\Button;

class TelegramButton
{

    private ?string $callbackData = null;

    public function __construct(private readonly string $name, ?string $callbackData = null)
    {
        $this->setCallbackData($callbackData);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallbackData(): ?string
    {
        return $this->callbackData;
    }

    public function setCallbackData(?string $callbackData): void
    {
        if ($callbackData !== null && strlen($callbackData) > 64) {
            throw new \InvalidArgumentException('Callback data must be 64 characters or less.');
        }
        $this->callbackData = $callbackData;
    }

    public function toArray(): array
    {
        $data = [
            'text' => $this->getName(),
        ];
        if ($this->getCallbackData() !== null) {
            $data['callback_data'] = $this->getCallbackData();
        }
        return $data;
    }

}