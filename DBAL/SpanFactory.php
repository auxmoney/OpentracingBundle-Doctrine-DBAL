<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

interface SpanFactory
{
    public function beforeOperation(string $sql): void;

    /**
     * @param array<mixed> $parameters
     */
    public function afterOperation(string $sql, array $parameters, ?string $username, int $affectedRowCount): void;

    public function addGeneralTags(?string $username): void;
}
