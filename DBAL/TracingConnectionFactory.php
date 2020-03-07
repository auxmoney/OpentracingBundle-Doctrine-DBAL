<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory as DoctrineConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use ReflectionException;
use ReflectionObject;

final class TracingConnectionFactory
{
    private $connectionFactory;
    private $tracing;
    private $spanFactory;

    public function __construct(
        DoctrineConnectionFactory $connectionFactory,
        Tracing $tracing,
        SpanFactory $spanFactory
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->tracing = $tracing;
        $this->spanFactory = $spanFactory;
    }

    /**
     * @param array<string,mixed> $params
     * @param string[]|Type[] $mappingTypes
     * @throws ReflectionException
     * @throws DBALException
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = []
    ): Connection {
        $connection = $this->connectionFactory->createConnection($params, $config, $eventManager, $mappingTypes);
        $driverConnection = new TracingDriverConnection(
            $connection->getWrappedConnection(),
            $this->tracing,
            $this->spanFactory,
            $connection->getUsername()
        );
        $reflectionObject = new ReflectionObject($connection);
        $property = $reflectionObject->getProperty('_conn');
        $property->setAccessible(true);
        $property->setValue($connection, $driverConnection);
        $property->setAccessible(false);
        return $connection;
    }
}
