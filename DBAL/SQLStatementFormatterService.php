<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingDoctrineDBALBundle\OpentracingDoctrineDBALBundle;

final class SQLStatementFormatterService implements SQLStatementFormatter
{
    private $previewLength;

    public function __construct(int $previewLength)
    {
        $this->previewLength = $previewLength;
    }

    public function formatForTracer(string $string): string
    {
        return OpentracingDoctrineDBALBundle::AUXMONEY_OPENTRACING_BUNDLE_TYPE . ': '
            . substr($string, 0, $this->previewLength);
    }
}
