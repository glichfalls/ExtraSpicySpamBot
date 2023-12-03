<?php declare(strict_types=1);

namespace App\Service\Honor;

use App\Entity\Honor\Season\Season;
use App\Entity\Honor\Season\SeasonFactory;
use App\Repository\Honor\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

final class SeasonService
{
    private ?Season $season = null;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly SeasonRepository $seasonRepository,
    ) {

    }

    public function getSeason(): Season
    {
        try {
            if ($this->season !== null) {
                return $this->season;
            }
            $season = $this->seasonRepository->fetchCurrentSeason();
            if ($season === null) {
                $season = $this->createSeason('Season 1');
            }
            $this->season = $season;
            return $season;
        } catch (NonUniqueResultException $exception) {
            throw new \RuntimeException('failed to load current season', previous: $exception);
        }
    }

    public function createSeason(string $name): Season
    {
        $season = SeasonFactory::create($name);
        $this->manager->persist($season);
        return $season;
    }

}
