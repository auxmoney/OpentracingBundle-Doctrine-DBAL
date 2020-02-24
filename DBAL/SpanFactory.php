<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

interface SpanFactory
{
    /**
     * @param array<mixed> $parameters
     */
    public function beforeOperation(string $sql, array $parameters, ?string $username): void;

    /**
     * @param array<mixed> $parameters
     */
    public function afterOperation(string $sql, array $parameters, ?string $username, int $affectedRowCount): void;
}
