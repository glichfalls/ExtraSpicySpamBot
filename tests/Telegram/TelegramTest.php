<?php

namespace App\Tests\Telegram;

use App\Service\Honor\HonorService;
use App\Service\Telegram\TelegramService;
use App\Tests\BaseKernelTest;
use PHPUnit\Framework\MockObject\MockObject;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Update;

class TelegramTest extends BaseKernelTest
{

    protected function createUpdate(
        ?CallbackQuery $callbackQuery = null,
    ): Update {
        $update = $this->createMock(Update::class);
        if ($callbackQuery !== null) {
            $update->expects(self::atLeastOnce())->method('getCallbackQuery')->willReturn($callbackQuery);
        }
        return $update;
    }

    protected function createCallbackQuery(string $data): CallbackQuery
    {
        $callbackQuery = $this->createMock(CallbackQuery::class);
        $callbackQuery->expects(self::atLeastOnce())->method('getData')->willReturn($data);
        return $callbackQuery;
    }

    protected function getTelegramService(): TelegramService&MockObject
    {
        $service = $this->createMock(TelegramService::class);
        $service->expects(self::any())->method('answerCallbackQuery');
        $service->expects(self::any())->method('sendText');
        $service->expects(self::any())->method('sendImage');
        $service->expects(self::any())->method('sendVideo');
        return $service;
    }

    protected function getHonorService(): HonorService
    {
        $service = $this->createMock(HonorService::class);
        $service->expects(self::any())->method('getCurrentHonorAmount')->willReturnArgument(0);
        return $service;
    }

}
