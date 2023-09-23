<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorFactory;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;

class HonorService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private HonorRepository $honorRepository,
    ) {

    }

    public function getLeaderboardByChat(Chat $chat): ?string
    {
        $leaderboard = $this->honorRepository->getLeaderboard($chat);
        if (count($leaderboard) === 0) {
            return null;
        } else {
            $text = array_map(function ($entry) {
                $name = $entry['firstName'] ?? $entry['name'];
                return sprintf(
                    '%s: %s Ehre',
                    $name,
                    NumberFormat::format($entry['amount'] + Honor::BASE_HONOR)
                );
            }, $leaderboard);
            return implode(PHP_EOL, $text);
        }
    }

    public function getCurrentHonorAmount(Chat $chat, User $user): int
    {
        return $this->honorRepository->getHonorCount($user, $chat);
    }

    public function addHonor(Chat $chat, User $recipient, int $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, abs($amount)));
    }

    public function removeHonor(Chat $chat, User $recipient, int $amount, ?User $sender = null): void
    {
        $this->manager->persist(HonorFactory::create($chat, $sender, $recipient, -abs($amount)));
    }

}
