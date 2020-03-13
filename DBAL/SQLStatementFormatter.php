<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

interface SQLStatementFormatter
{
    public function formatForTracer(string $string): string;
}
