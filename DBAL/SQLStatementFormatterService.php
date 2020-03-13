<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingDoctrineDBALBundle\OpentracingDoctrineDBALBundle;

final class SQLStatementFormatterService implements SQLStatementFormatter
{
    public function formatForTracer(string $string): string
    {
        $formattedStatement = substr($string, 0, 32);
        $matches = [];
        if (preg_match('/^(SELECT|DELETE).* FROM ([\S]+)/i', $string, $matches) ||
                preg_match('/^(INSERT INTO|UPDATE) ([\S]+)/i', $string, $matches)) {
            array_shift($matches);
            $formattedStatement = implode(' ', $matches);
        }
        return OpentracingDoctrineDBALBundle::AUXMONEY_OPENTRACING_BUNDLE_TYPE . ': ' . $formattedStatement;
    }
}
