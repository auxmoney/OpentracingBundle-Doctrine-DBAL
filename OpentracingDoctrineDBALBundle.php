<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OpentracingDoctrineDBALBundle extends Bundle
{
    public const AUXMONEY_OPENTRACING_BUNDLE_TYPE = 'DBAL';
}
