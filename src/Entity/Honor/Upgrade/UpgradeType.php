<?php declare(strict_types=1);

namespace App\Entity\Honor\Upgrade;

use App\Model\Id;
use App\Repository\HonorUpgradeRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Money\Money;

#[Entity(repositoryClass: HonorUpgradeRepository::class)]
class UpgradeType
{
    use Id;

    #[Column(type: 'string', unique: true, nullable: false)]
    private string $name;

    #[Column(type: 'string', unique: true, nullable: false)]
    private string $code;

    #[Column(type: 'honor', nullable: false)]
    private Money $price;

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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): void
    {
        $this->price = $price;
    }

}