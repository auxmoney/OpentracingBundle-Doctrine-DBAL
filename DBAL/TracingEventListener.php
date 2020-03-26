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
        $username = $connection->getUsername();

        $wrappedConnection = new TracingDriverConnection(
            $connection->getWrappedConnection(),
            $this->tracing,
            $this->spanFactory,
            $username
        );

        $reflectionObject = new ReflectionObject($connection);
        $property = $reflectionObject->getProperty('_conn');
        $property->setAccessible(true);
        $property->setValue($connection, $wrappedConnection);
        $property->setAccessible(false);

        // Account for already started transactions (used by autocommit)
        $inFlight = $connection->getTransactionNestingLevel();

        for ($i = 0; $i < $inFlight; $i++) {
            $this->tracing->startActiveSpan('DBAL: TRANSACTION');
            $this->spanFactory->addGeneralTags($username);
        }
    }
}
