<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Annotation\UserAware;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function __construct(private Security $security, private Reader $reader)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void
    {
        if (class_exists($resourceClass)) {
            $reflection = new \ReflectionClass($resourceClass);
            $annotation = $reflection->getAttributes(UserAware::class)[0] ?? null;
            dump($annotation);
            if ($annotation instanceof UserAware) {
                $operation = match ($annotation->getLogicalOperation()) {
                    UserAware::OPERATION_OR => new Andx(),
                    UserAware::OPERATION_AND => new Orx(),
                    default => null,
                };
                if ($operation === null) {
                    dump('Invalid logical operation');
                    return;
                }
                $rootAlias = $queryBuilder->getRootAliases()[0];
                foreach ($annotation->getFieldNames() as $fieldName) {
                    $operation->add($queryBuilder->expr()->eq(sprintf('%s.%s', $rootAlias, $fieldName), ':user'));
                }
                $queryBuilder->setParameter('user', $this->security->getUser());
                $queryBuilder->andWhere($operation);
            }
        }
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void
    {
        // TODO: Implement applyToItem() method.
    }

}