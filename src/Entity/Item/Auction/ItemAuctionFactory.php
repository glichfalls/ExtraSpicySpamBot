<?php

namespace App\Entity\Item\Auction;

use App\Entity\Honor\Honor;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use Money\Money;

class ItemAuctionFactory
{

    public static function create(
        ItemInstance $instance,
        ?User $seller,
        ?User $highestBidder,
        ?Money $highestBid,
        bool $active = true,
    ): ItemAuction {
        $auction = new ItemAuction();
        $auction->setInstance($instance);
        $auction->setSeller($seller);
        $auction->setHighestBidder($highestBidder);
        $auction->setHighestBid($highestBid ?? Honor::currency(0));
        $auction->setActive($active);
        $auction->setCreatedAt(new \DateTime());
        $auction->setUpdatedAt(new \DateTime());
        return $auction;
    }

}
