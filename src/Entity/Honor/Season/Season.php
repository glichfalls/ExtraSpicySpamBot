<?php declare(strict_types=1);

namespace App\Entity\Honor\Season;

use App\Model\Id;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Season
{
    use Id;

    #[Column(type: 'date')]
    private DateTimeInterface $start;

    #[Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $end = null;

    #[Column(type: 'string')]
    private string $name;

    public function __construct()
    {
        $this->generateId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(DateTimeInterface $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $end): void
    {
        $this->end = $end;
    }

}
