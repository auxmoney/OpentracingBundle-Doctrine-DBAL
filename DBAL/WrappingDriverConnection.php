<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Doctrine\DBAL\Driver\Connection;

interface WrappingDriverConnection
{
    /**
     * Returns the wrapped connection.
     *
     * Keep in mind that operations made on this connection won't be traced!
     */
    public function getWrappedConnection(): Connection;
}
