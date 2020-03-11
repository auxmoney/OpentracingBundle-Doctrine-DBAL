<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Doctrine\DBAL\Driver\Statement;

interface WrappingStatement
{
    /**
     * Returns the wrapped statement.
     *
     * Keep in mind that operations made on this statement won't be traced!
     *
     * @return Statement<Statement>
     */
    public function getWrappedStatement(): Statement;
}
