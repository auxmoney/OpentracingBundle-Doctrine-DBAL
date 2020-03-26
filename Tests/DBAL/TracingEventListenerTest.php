<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SpanFactory;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\TracingEventListener;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DBALDriverConnection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use PHPUnit\Framework\TestCase;

class TracingEventListenerTest extends TestCase
{
    private $tracing;
    private $spanFactory;

    /** @var TracingEventListener */
    private $subject;

    public function setUp()
    {
        parent::setUp();
        $this->tracing = $this->prophesize(Tracing::class);
        $this->spanFactory = $this->prophesize(SpanFactory::class);

        $this->subject = new TracingEventListener(
            $this->tracing->reveal(), $this->spanFactory->reveal()
        );
    }

    public function testInflightTransactions(): void
    {
        $driverConnection = $this->prophesize(DBALDriverConnection::class);

        $connection = $this->prophesize(Connection::class);
        $connection->getTransactionNestingLevel()->willReturn(1);
        $connection->getUsername()->willReturn('username');
        $connection->getWrappedConnection()->willReturn($driverConnection->reveal());

        $connectionEventArgs = $this->prophesize(ConnectionEventArgs::class);
        $connectionEventArgs->getConnection()->willReturn($connection->reveal());

        $connection->getUsername()->shouldBeCalled();
        $connection->getTransactionNestingLevel()->shouldBeCalled();
        $this->tracing->startActiveSpan('DBAL: TRANSACTION')->shouldBeCalled();
        $this->spanFactory->addGeneralTags('username')->shouldBeCalled();
        
        $this->subject->postConnect($connectionEventArgs->reveal());
    }

    public function testNoInflightTransactions(): void
    {
        $driverConnection = $this->prophesize(DBALDriverConnection::class);

        $connection = $this->prophesize(Connection::class);
        $connection->getTransactionNestingLevel()->willReturn(0);
        $connection->getUsername()->willReturn('username');
        $connection->getWrappedConnection()->willReturn($driverConnection->reveal());

        $connectionEventArgs = $this->prophesize(ConnectionEventArgs::class);
        $connectionEventArgs->getConnection()->willReturn($connection->reveal());

        $connection->getUsername()->shouldBeCalled();
        $connection->getTransactionNestingLevel()->shouldBeCalled();
        $this->tracing->startActiveSpan()->shouldNotBeCalled();
        $this->spanFactory->addGeneralTags()->shouldNotBeCalled();
        
        $this->subject->postConnect($connectionEventArgs->reveal());
    }
}
