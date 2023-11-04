<?php

namespace App\Tests\Item;

use App\Repository\ItemAuctionRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractItemTest extends KernelTestCase
{

    public function getAuctionRepository(ContainerInterface $container): ItemAuctionRepository
    {
        $repository = $this->createMock(ItemAuctionRepository::class);
        $repository->expects()
        return $repository;
    }

}