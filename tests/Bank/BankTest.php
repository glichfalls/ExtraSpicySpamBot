<?php declare(strict_types=1);

namespace App\Tests\Bank;

use App\Service\Honor\BankService;
use App\Tests\BaseKernelTest;

class BankTest extends BaseKernelTest
{

    private function getBankService(): BankService
    {
        return $this->getContainer()->get(BankService::class);
    }

    public function testAccountCreation(): void
    {
        $service = $this->getBankService();

        $service->createBankAccount($this->getChat(), $this->getUser());
    }

}