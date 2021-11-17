<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory as DoctrineConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Types\Type;

final class TracingConnectionFactory
{
    private $connectionFactory;
    private $eventListener;

    public function __construct(
        DoctrineConnectionFactory $connectionFactory,
        Tracing $tracing,
        SpanFactory $spanFactory
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->eventListener = new TracingEventListener(
            $tracing,
            $spanFactory
        );
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,string> $mappingTypes
     * @throws DBALException
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = []
    ): Connection {
        $connection = $this->connectionFactory->createConnection($params, $config, $eventManager, $mappingTypes);
        $connection->getEventManager()->addEventListener(Events::postConnect, $this->eventListener);
        return $connection;
    }
}
