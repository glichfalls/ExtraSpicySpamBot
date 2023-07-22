<?php

namespace App\ApiPlatform\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class UserFilter extends AbstractContextAwareFilter
{

    public function __construct(
        ManagerRegistry $managerRegistry,
        ?RequestStack $requestStack = null,
        LoggerInterface $logger = null,
        array $properties = null,
        NameConverterInterface $nameConverter = null,
        private ?Security $security = null,
    )
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        mixed $queryNameGenerator,
        string $resourceClass,
        string $operationName = null,
        array $context = []
    ) {
        if ('userId' !== $property) {
            return;
        }
        exit;
        $alias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();
        if($user !== null){
            $userId = $user->getId();
            $queryBuilder
                ->andWhere(sprintf('%s.userId = :current_user', $alias))
                ->setParameter('current_user', $userId);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'userId' => [
                'property' => 'userId',
                'type' => 'integer',
                'required' => false,
                'swagger' => [
                    'description' => 'Filter based on the current user ID',
                    'name' => 'Current User Filter',
                    'type' => 'filter',
                ],
            ],
        ];
    }
}
