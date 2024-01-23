<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\Season\Season;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Persistence\ManagerRegistry;
use Money\Money;

class HonorRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Honor::class);
    }

    public function getHonorCount(Season $season, User $user, Chat $chat): Money
    {
        try {
            $queryBuilder = $this->createQueryBuilder('h');
            $queryBuilder
                ->select('SUM(h.amount)')
                ->where('h.recipient = :user')
                ->andWhere('h.chat = :chat')
                ->andWhere('h.season = :season')
                ->setParameter('season', $season)
                ->setParameter('user', $user)
                ->setParameter('chat', $chat);
            $amount = $queryBuilder->getQuery()->getSingleScalarResult() ?? 0;
            return Honor::currency((int) $amount);
        } catch (UnexpectedResultException $exception) {
            return Honor::currency(0);
        }
    }

    public function getLeaderboard(Season $season, Chat $chat): array
    {
        return $this->createQueryBuilder('h')
            ->select('r.id, r.name, r.firstName, SUM(h.amount) as amount')
            ->join('h.recipient', 'r')
            ->where('h.chat = :chat')
            ->andWhere('h.season = :season')
            ->setParameter('chat', $chat)
            ->setParameter('season', $season)
            ->groupBy('r.id')
            ->orderBy('amount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws UnexpectedResultException
     */
    public function getLastChange(User $sender, User $recipient, Chat $chat): ?Honor
    {
        return $this->createQueryBuilder('h')
            ->where('h.chat = :chat')
            ->andWhere('h.sender = :sender')
            ->andWhere('h.recipient = :recipient')
            ->setParameter('chat', $chat)
            ->setParameter('sender', $sender)
            ->setParameter('recipient', $recipient)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
