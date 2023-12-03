<?php declare(strict_types=1);

namespace App\Types;

use App\Entity\Honor\Honor;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;
use Money\Money;

class HonorType extends DecimalType
{
    public const TYPE = 'honor';

    public function getName(): string
    {
        return self::TYPE;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL([
            'precision' => 63,
            'scale' => 2,
        ]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof Money) {
            return $value->getAmount();
        }
        return null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }
        return Honor::currency($value);
    }

}