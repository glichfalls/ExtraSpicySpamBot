<?php

namespace App\Tests\Item;

use App\Entity\Item\Auction\ItemAuction;
use App\Service\Telegram\Honor\Items\Trade\OpenItemTradeChatCommand;
use App\Tests\Telegram\TelegramTest;

class ItemTradeTest extends TelegramTest
{

    public function testCreateAuction(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $seller = $this->createTestUser(1);
        $buyer = $this->createTestUser(2);

        $auction = new ItemAuction();
        $auction->setSeller($seller);
        $auction->setHighestBidder($buyer);
        $auction->setHighestBid(100);
        $auction->setActive(true);

        $manager = $this->getEntityManager();
        $manager->persist($auction);
        $manager->flush();

        $command = $container->get(OpenItemTradeChatCommand::class);

        $callbackQuery = $this->createCallbackQuery('trade:open:1');
        $update = $this->createUpdate(callbackQuery: $callbackQuery);

        $chat = $this->createTestChat(1);

        $command->handleCallback($update, $chat, $buyer);
    }

}