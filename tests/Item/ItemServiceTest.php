<?php

namespace App\Tests\Service\Items;

use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Item;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Service\Items\ItemService;
use PHPUnit\Framework\TestCase;

class ItemServiceTest extends TestCase
{
    public function itemExecutionWithValidOwnerAndAttributesDoesNotThrowException(): void
    {
        $item = $this->createMock(Item::class);
        $item->method('hasAttribute')->willReturn(true);

        $instance = $this->createMock(ItemInstance::class);
        $instance->method('getOwner')->willReturn(new User());
        $instance->method('getItem')->willReturn($item);
        $instance->method('isExpired')->willReturn(false);
        $instance->method('hasPayloadValue')->willReturn(true);

        $service = new ItemService();
        $service->validateItemExecution($instance, new User());
    }

    public function itemExecutionWithInvalidOwnerThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You are not the owner of this item.');

        $instance = $this->createMock(ItemInstance::class);
        $instance->method('getOwner')->willReturn(new User());

        $service = new ItemService();
        $service->validateItemExecution($instance, new User());
    }

    public function itemExecutionWithNonExecutableItemThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This item cannot be executed.');

        $item = $this->createMock(Item::class);
        $item->method('hasAttribute')->willReturn(false);

        $instance = $this->createMock(ItemInstance::class);
        $instance->method('getOwner')->willReturn(new User());
        $instance->method('getItem')->willReturn($item);

        $service = new ItemService();
        $service->validateItemExecution($instance, new User());
    }

    public function itemExecutionWithExpiredItemThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This item is expired.');

        $item = $this->createMock(Item::class);
        $item->method('hasAttribute')->willReturn(true);

        $instance = $this->createMock(ItemInstance::class);
        $instance->method('getOwner')->willReturn(new User());
        $instance->method('getItem')->willReturn($item);
        $instance->method('isExpired')->willReturn(true);

        $service = new ItemService();
        $service->validateItemExecution($instance, new User());
    }

    public function itemExecutionWithoutExecutableNameThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This item cannot be executed.');

        $item = $this->createMock(Item::class);
        $item->method('hasAttribute')->willReturn(true);

        $instance = $this->createMock(ItemInstance::class);
        $instance->method('getOwner')->willReturn(new User());
        $instance->method('getItem')->willReturn($item);
        $instance->method('isExpired')->willReturn(false);
        $instance->method('hasPayloadValue')->with('executable_name')->willReturn(false);

        $service = new ItemService();
        $service->validateItemExecution($instance, new User());
    }

    public function itemExecutionWithAlreadyExecutedTodayThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This item can only be used once per day.');

        $item = $this->createMock(Item::class);
        $item->method('hasAttribute')->willReturn(true);

        $instance = $this->createMock(ItemInstance::class);
        $instance->method('getOwner')->willReturn(new User());
        $instance->method('getItem')->willReturn($item);
        $instance->method('isExpired')->willReturn(false);
        $instance->method('hasPayloadValue')->with('executable_name')->willReturn(true);
        $instance->method('getPayloadValue')->with('last_execution')->willReturn(date('Y-m-d'));

        $service = new ItemService();
        $service->validateItemExecution($instance, new User());
    }
}