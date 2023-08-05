<?php

namespace App\Annotation;

use Attribute;

#[Attribute]
class UserAware
{

    public const OPERATION_AND = 'and';
    public const OPERATION_OR = 'or';

    /**
     * @param array<string> $fieldNames
     * @param string $logicalOperation
     */
    public function __construct(
        private array $fieldNames = ['user'],
        private string $logicalOperation = self::OPERATION_OR,
    )
    {

    }

    /**
     * @return string[]
     */
    public function getFieldNames(): array
    {
        return $this->fieldNames;
    }

    public function getLogicalOperation(): string
    {
        return $this->logicalOperation;
    }

}