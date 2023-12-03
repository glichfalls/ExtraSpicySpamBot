<?php declare(strict_types=1);

namespace App\Tests\Honor;

use App\Entity\Chat\ChatFactory;
use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorFactory;
use App\Entity\User\User;
use App\Tests\BaseKernelTest;
use Money\Money;

class HonorCurrencyTest extends BaseKernelTest
{

    public function testCreateAndInsertHonor(): void
    {
        $user = $this->getUser();
        $chat = $this->getChat();
        $honor = HonorFactory::createPositive($chat, $this->getTestUser(), Honor::currency(100));
        $honor = $this->getEntityManager()->getRepository(Honor::class)->findOneBy(['id' => $honor->getId()]);
        $this->assertInstanceOf(Honor::class, $honor);
        $this->assertInstanceOf(Money::class, $honor->getAmount());
        $this->assertEquals(100, $honor->getAmount()->getAmount());
        $this->assertEquals('EHRE', $honor->getAmount()->getCurrency()->getCode());
        $this->getEntityManager()->remove($honor);
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->remove($chat);
        $this->getEntityManager()->flush();
    }

    private function getUser(): User
    {
        $user = new User();
        $user->setTelegramUserId(123456789);
        $user->setName('test');
        $user->setFirstName('test');
        $user->setLastName('test');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        return $user;
    }

    public function getChat(): Chat
    {
        $chat = ChatFactory::create('123456789', 'test');
        $chat->setConfig(new ChatConfig());
        $chat->addUser($this->getUser());
        return $chat;
    }

}