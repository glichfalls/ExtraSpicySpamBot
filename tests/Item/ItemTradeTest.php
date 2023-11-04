<?php

namespace App\Tests\Item;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\Item\ItemAuction;
use App\Entity\User\User;
use App\Entity\User\UserFactory;
use App\Repository\HonorRepository;
use App\Repository\ItemAuctionRepository;
use App\Service\HonorService;
use App\Service\Items\CollectableService;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\Honor\Collectables\Trade\OpenItemTradeChatCommand;
use App\Service\Telegram\TelegramService;
use App\Tests\Telegram\TelegramTest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Update;

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

        $auctionRepository = $this->createMock(ItemAuctionRepository::class);
        //$auctionRepository->expects(self::once())->method('Item')->willReturn(new ItemAuction());

        $command = new OpenItemTradeChatCommand(
            $this->getEntityManager(),
            $this->getTranslator(),
            $this->getLogger(),
            $this->getTelegramService(),
            $this->getHonorService(),
            $container->get(HonorRepository::class),
            $container->get(CollectableService::class),
            $container->get(ItemService::class),
            $container->get(ItemTradeService::class),
        );

        $callbackQuery = $this->createCallbackQuery('trade:open:1');
        $update = $this->createUpdate(callbackQuery: $callbackQuery);

        $chat = $this->createTestChat(1);

        $command->handleCallback($update, $chat, $buyer);
    }

}