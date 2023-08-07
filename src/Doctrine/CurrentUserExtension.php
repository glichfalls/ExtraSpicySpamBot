<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Annotation\UserAware;
use App\Entity\User\User;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->addQuery($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->addQuery($queryBuilder, $resourceClass);
    }

    private function addQuery(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (class_exists($resourceClass)) {
            $reflectionClass = new \ReflectionClass($resourceClass);
            $attribute = $reflectionClass->getAttributes(UserAware::class);
            if (count($attribute) > 0) {
                /** @var UserAware $userAware */
                $userAware = $attribute[0]->newInstance();
                $user = $this->security->getUser();
                if ($user instanceof User && !in_array(User::ROLE_ADMIN, $user->getRoles())) {
                    $query = $userAware->getLogicalOperation() === UserAware::OPERATION_AND ? new Andx() : new Orx();
                    foreach ($userAware->getFieldNames() as $fieldName) {
                        $query->add(
                            $queryBuilder->expr()
                                ->isMemberOf(':userId', sprintf('o.%s', $fieldName)),
                        );
                    }
                    $queryBuilder->andWhere($query);
                    $queryBuilder->setParameter('userId', $user->getId());
                }
            }
        }
    }

}
