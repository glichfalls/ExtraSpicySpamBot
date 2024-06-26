<?php

namespace App\Entity\Stocks\Portfolio;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Chat\Chat;
use App\Entity\Honor\Season\Season;
use App\Entity\Stocks\Stock\StockPrice;
use App\Entity\Stocks\Transaction\StockTransaction;
use App\Entity\Stocks\Transaction\SymbolTransactionCollection;
use App\Entity\User\User;
use App\Model\Id;
use App\Repository\Stocks\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity(repositoryClass: PortfolioRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['portfolio:read', 'price:read', 'user:read', 'chat:read']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'chat' => 'exact',
    'chat.id' => 'exact',
    'user' => 'exact',
    'user.id' => 'exact',
])]
class Portfolio
{
    use Id;

    #[ManyToOne(targetEntity: Chat::class)]
    #[Groups(['chat:read'])]
    private Chat $chat;

    #[ManyToOne(targetEntity: User::class)]
    #[Groups(['user:read'])]
    private User $user;

    #[ManyToOne(targetEntity: Season::class)]
    #[Groups(['portfolio:read'])]
    private Season $season;

    #[OneToMany(mappedBy: 'portfolio', targetEntity: StockTransaction::class, cascade: ['persist'])]
    #[OrderBy(['createdAt' => 'DESC'])]
    #[Groups(['portfolio:read', 'price:read'])]
    private Collection $transactions;

    public function __construct()
    {
        $this->generateId();
        $this->transactions = new ArrayCollection();
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): void
    {
        $this->chat = $chat;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): void
    {
        $this->season = $season;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(StockTransaction $transaction): void
    {
        $transaction->setPortfolio($this);
        $this->transactions->add($transaction);
    }

    public function getTransactionsBySymbol(string $symbol, ?StockPrice $currentPrice = null): SymbolTransactionCollection
    {
        return new SymbolTransactionCollection($symbol, $currentPrice, $this->getTransactions());
    }

    /**
     * @return array<SymbolTransactionCollection>
     */
    #[Groups(['portfolio:read'])]
    public function getBalance(): array
    {
        $symbols = $this->getTransactions()->map(fn (StockTransaction $transaction) => $transaction->getPrice()->getStock()->getSymbol());
        $symbols = array_unique($symbols->toArray());
        $data = array_map(fn (string $symbol) => $this->getTransactionsBySymbol($symbol), $symbols);
        usort($data, fn (SymbolTransactionCollection $a, SymbolTransactionCollection $b) => bccomp($a->getCurrentTotal(), $b->getCurrentTotal()));
        return $data;
    }

}