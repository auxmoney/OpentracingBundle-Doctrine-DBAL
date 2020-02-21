<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OpentracingDoctrineDBALBundle extends Bundle
{
    // TODO functional tests

    // TODO: idea for tags: span.kind = client
    // TODO: idea for tags: db.type = sql
    // TODO: idea for tags: db.instance                                  | toggable
    // TODO: idea for tags: db.user                                      | toggable
    // TODO: idea for tags: db.statement                                 | toggable if preview only, preview length configurable / first word
    // TODO: idea for tags: db.parameters                                | toggable
    // TODO: idea for tags: db.row_count (affected rows / returned rows) | toggable
}
