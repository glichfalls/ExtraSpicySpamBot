<?php declare(strict_types=1);

namespace App\Service\Honor;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Bank\BankAccount;
use App\Entity\Honor\Bank\Transaction;
use App\Entity\Honor\Bank\TransactionFactory;
use App\Entity\Honor\Season\Season;
use App\Entity\User\User;
use App\Repository\BankAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Money\Money;

readonly class BankService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private BankAccountRepository $bankAccountRepository,
        private HonorService $honorService,
        private SeasonService $seasonService,
    ) {

    }

    public function createBankAccount(Chat $chat, User $user): BankAccount
    {
        $season = $this->seasonService->getSeason();
        $account = new BankAccount();
        $account->setSeason($season);
        $account->setChat($chat);
        $account->setUser($user);
        $this->manager->persist($account);
        $this->manager->flush();
        return $account;
    }

    public function getAccount(Chat $chat, User $user, ?Season $season = null): BankAccount
    {
        if ($season === null) {
            $season = $this->seasonService->getSeason();
        }
        try {
            return $this->bankAccountRepository->getByChatAndUser($season, $chat, $user)
                ?? $this->createBankAccount($chat, $user);
        } catch (NonUniqueResultException $exception) {
            throw new \RuntimeException('multiple bank accounts found', previous: $exception);
        }
    }

    public function deposit(Chat $chat, User $user, Money $amount): Transaction
    {
        $account = $this->getAccount($chat, $user);
        $honor = $this->honorService->getCurrentHonorAmount($account->getChat(), $account->getUser());
        if ($honor->lessThan($amount)) {
            throw new \RuntimeException('not enough honor');
        }
        $transaction = $this->createTransaction($account, $amount);
        $this->manager->flush();
        return $transaction;
    }

    public function withdraw(Chat $chat, User $user, Money $amount): Transaction
    {
        $account = $this->getAccount($chat, $user);
        if ($account->getBalance()->lessThan($amount)) {
            throw new \RuntimeException('not enough ehre in bank account');
        }
        $transaction = $this->createTransaction($account, $amount->negative());
        $this->manager->flush();
        return $transaction;
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
