<?php

namespace App\Serializer;

use App\Entity\Item\Attribute\ItemRarity;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ItemRaritySerializer implements DenormalizerInterface, NormalizerInterface
{
    public function getSupportedTypes(?string $format): array
    {
        return [ItemRarity::class];
    }

    public function denormalize($data, $type, $format = null, array $context = []): ItemRarity
    {
        if ($type !== ItemRarity::class) {
            throw new \InvalidArgumentException('Only ItemRarity type is supported.');
        }

        return ItemRarity::from(strtolower($data['name'] ?? ''));
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return $type === ItemRarity::class;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        if (!$object instanceof ItemRarity) {
            throw new \InvalidArgumentException('The object must be an instance of ItemRarity.');
        }

        return $object->jsonSerialize();
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof ItemRarity;
    }
}