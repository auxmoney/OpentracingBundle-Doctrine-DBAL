<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use ReflectionException;
use ReflectionObject;

final class TracingEventListener
{
    private $tracing;
    private $spanFactory;

    public function __construct(
        Tracing $tracing,
        SpanFactory $spanFactory
    ) {
        $this->tracing = $tracing;
        $this->spanFactory = $spanFactory;
    }

    /**
     * @param ConnectionEventArgs $args
     * @throws ReflectionException
     */
    public function postConnect(ConnectionEventArgs $args): void
    {
        $connection = $args->getConnection();
        $reflectionObject = new ReflectionObject($connection);
        $property = $reflectionObject->getProperty('_conn');
        $property->setAccessible(true);
        $previousConnection = $property->getValue($connection);
        $driverConnection = new TracingDriverConnection(
            $previousConnection,
            $this->tracing,
            $this->spanFactory,
            $connection->getUsername()
        );
        $property->setValue($connection, $driverConnection);
        $property->setAccessible(false);
    }
}
