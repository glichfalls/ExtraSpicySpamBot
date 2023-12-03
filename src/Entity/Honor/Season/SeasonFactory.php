<?php declare(strict_types=1);

namespace App\Entity\Honor\Season;

class SeasonFactory
{
    public static function create(string $name): Season
    {
        $season = new Season();
        $season->setName($name);
        $season->setStart(new \DateTimeImmutable());
        return $season;
    }
}