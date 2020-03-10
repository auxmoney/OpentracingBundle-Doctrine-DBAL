<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SpanFactory;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\TracingDriverConnection;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\TracingStatement;
use Doctrine\DBAL\Driver\Connection as DBALDriverConnection;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\TestCase;

class TracingDriverConnectionTest extends TestCase
{
    private $decoratedConnection;
    private $tracing;
    private $spanFactory;
    private $username;
    /** @var TracingDriverConnection */
    private $subject;

    public function setUp()
    {
        parent::setUp();
        $this->decoratedConnection = $this->prophesize(DBALDriverConnection::class);
        $this->tracing = $this->prophesize(Tracing::class);
        $this->spanFactory = $this->prophesize(SpanFactory::class);
        $this->username = 'dbuser';

        $this->subject = new TracingDriverConnection(
            $this->decoratedConnection->reveal(),
            $this->tracing->reveal(),
            $this->spanFactory->reveal(),
            $this->username
        );
    }

    public function testErrorInfo(): void
    {
        $this->decoratedConnection->errorInfo()->willReturn(['error' => 'info']);

        self::assertSame(['error' => 'info'], $this->subject->errorInfo());
    }

    public function testLastInsertId(): void
    {
        $this->decoratedConnection->lastInsertId(null)->willReturn('id5');

        self::assertSame('id5', $this->subject->lastInsertId(null));
    }

    public function testBeginTransaction(): void
    {
        $this->tracing->startActiveSpan('DBAL: TRANSACTION')->shouldBeCalled();
        $this->spanFactory->addGeneralTags($this->username)->shouldBeCalled();
        $this->decoratedConnection->beginTransaction()->shouldBeCalled();

        self::assertTrue($this->subject->beginTransaction());
    }

    public function testQuote(): void
    {
        $this->decoratedConnection->quote('input', 4)->willReturn('"input"');

        self::assertSame('"input"', $this->subject->quote('input', 4));
    }

    public function testPrepare(): void
    {
        $statement = $this->prophesize(Statement::class);
        $this->decoratedConnection->prepare('prepare string')->willReturn($statement->reveal());

        self::assertInstanceOf(TracingStatement::class, $this->subject->prepare('prepare string'));
    }

    public function testExec(): void
    {
        $this->spanFactory->beforeOperation('SQL statement')->shouldBeCalled();
        $this->decoratedConnection->exec('SQL statement')->willReturn(5);
        $this->spanFactory->afterOperation('SQL statement', [], $this->username, 5)->shouldBeCalled();

        self::assertSame(5, $this->subject->exec('SQL statement'));

    }

    public function testCommit(): void
    {
        $this->decoratedConnection->commit()->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.transaction.end', 'commit')->shouldBeCalled();
        $this->tracing->finishActiveSpan()->shouldBeCalled();

        self::assertTrue($this->subject->commit());
    }

    public function testErrorCode(): void
    {
        $this->decoratedConnection->errorCode()->willReturn('error');

        self::assertSame('error', $this->subject->errorCode());
    }

    public function testQuery(): void
    {
        $statement = $this->prophesize(Statement::class);
        $statement->rowCount()->shouldBeCalled()->willReturn(4);
        $this->spanFactory->beforeOperation('query statement')->shouldBeCalled();
        $this->decoratedConnection->query('query statement', 'param 1')->shouldBeCalled()->willReturn($statement->reveal());
        $this->spanFactory->afterOperation('query statement', ['param 1'], $this->username, 4);

        self::assertInstanceOf(TracingStatement::class, $this->subject->query('query statement', 'param 1'));
    }

    public function testRollBack(): void
    {
        $this->decoratedConnection->rollBack()->shouldBeCalled()->willReturn(true);
        $this->tracing->setTagOfActiveSpan('db.transaction.end', 'rollBack')->shouldBeCalled();
        $this->tracing->finishActiveSpan()->shouldBeCalled();

        self::assertTrue($this->subject->rollBack());
    }

    public function testGetWrappedConnection(): void
    {
        self::assertSame($this->decoratedConnection->reveal(), $this->subject->getWrappedConnection());
    }
}
