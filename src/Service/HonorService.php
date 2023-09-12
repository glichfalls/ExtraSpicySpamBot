<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Repository\HonorRepository;

class HonorService
{

    public function __construct(private HonorRepository $honorRepository)
    {

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
                    number_format($entry['amount'] + Honor::BASE_HONOR, thousands_separator: '\'')
                );
            }, $leaderboard);
            return implode(PHP_EOL, $text);
        }
    }

}
