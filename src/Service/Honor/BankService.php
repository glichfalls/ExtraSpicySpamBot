<?php declare(strict_types=1);

namespace App\Service\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Bank\BankAccount;
use App\Entity\Honor\Bank\Transaction;
use App\Entity\Honor\Bank\TransactionFactory;
use App\Entity\User\User;
use App\Repository\BankAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

readonly class BankService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private BankAccountRepository $bankAccountRepository,
        private HonorService $honorService,
    )
    {

    }

    public function createBankAccount(Chat $chat, User $user): BankAccount
    {
        $account = new BankAccount();
        $account->setChat($chat);
        $account->setUser($user);
        $this->manager->persist($account);
        $this->manager->flush();
        return $account;
    }

    public function getBankAccount(Chat $chat, User $user): BankAccount
    {
        return $this->bankAccountRepository->getByChatAndUser($chat, $user) ?? $this->createBankAccount($chat, $user);
    }

    public function getBalance(Chat $chat, User $user): Money
    {
        return $this->getBankAccount($chat, $user)->getBalance();
    }

    public function deposit(Chat $chat, User $user, Money $amount): Transaction
    {
        $account = $this->getBankAccount($chat, $user);
        $this->validateDeposit($account, $amount);
        $transaction = $this->createTransaction($account, $amount);
        $this->manager->flush();
        return $transaction;
    }

    public function withdraw(Chat $chat, User $user, Money $amount): Transaction
    {
        $account = $this->getBankAccount($chat, $user);
        $this->validateWithdraw($account, $amount);
        $transaction = $this->createTransaction($account, $amount->negative());
        $this->manager->flush();
        return $transaction;
    }

    private function validateDeposit(BankAccount $account, Money $amount): void
    {
        $honor = $this->honorService->getCurrentHonorAmount($account->getChat(), $account->getUser());
        if ($honor->lessThan($amount)) {
            throw new \RuntimeException('not enough honor');
        }
    }

    private function validateWithdraw(BankAccount $account, Money $amount): void
    {
        if ($account->getBalance()->lessThan($amount)) {
            throw new \RuntimeException('not enough ehre in bank account');
        }
    }

    private function createTransaction(BankAccount $account, Money $amount): Transaction
    {
        $transaction = TransactionFactory::create($amount);
        $account->addTransaction($transaction);
        $this->manager->persist($transaction);
        if ($amount->isNegative()) {
            $this->honorService->addHonor($account->getChat(), $account->getUser(), $amount);
        } else {
            $this->honorService->removeHonor($account->getChat(), $account->getUser(), $amount);
        }
        return $transaction;
    }

}
