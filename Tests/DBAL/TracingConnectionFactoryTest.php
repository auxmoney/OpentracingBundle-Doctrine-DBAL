<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SpanFactory;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\TracingConnectionFactory;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\TracingDriverConnection;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory as DoctrineConnectionFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use PHPUnit\Framework\TestCase;

class TracingConnectionFactoryTest extends TestCase
{
    private $connectionFactory;
    private $tracing;
    private $spanFactory;
    /** @var TracingConnectionFactory */
    private $subject;

    public function setUp()
    {
        parent::setUp();
        $this->connectionFactory = $this->prophesize(DoctrineConnectionFactory::class);
        $this->tracing = $this->prophesize(Tracing::class);
        $this->spanFactory = $this->prophesize(SpanFactory::class);

        $this->subject = new TracingConnectionFactory(
            $this->connectionFactory->reveal(), $this->tracing->reveal(), $this->spanFactory->reveal()
        );
    }

    public function testCreateConnection(): void
    {
        $originalConnection = new Connection([], new Driver());
        $this->connectionFactory->createConnection(['param' => 'param value'], null, null, [])->willReturn($originalConnection);

        $connection = $this->subject->createConnection(['param' => 'param value']);
        self::assertInstanceOf(TracingDriverConnection::class, $connection->getWrappedConnection());
    }
}
