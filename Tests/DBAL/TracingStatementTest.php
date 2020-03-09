<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SpanFactory;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\TracingStatement;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\TestCase;

class TracingStatementTest extends TestCase
{
    private $statement;
    private $spanFactory;
    private $sql;
    private $username;
    /** @var TracingStatement */
    private $subject;

    public function setUp()
    {
        parent::setUp();
        $this->statement = $this->prophesize(Statement::class);
        $this->spanFactory = $this->prophesize(SpanFactory::class);
        $this->sql = 'original sql';
        $this->username = 'dbuser';

        $this->subject = new TracingStatement(
            $this->statement->reveal(),
            $this->spanFactory->reveal(),
            $this->sql,
            $this->username
        );
    }

    public function testFetch(): void
    {
        $this->statement->fetch(1, 2, 3)->shouldBeCalled()->willReturn(['result']);

        self::assertSame(['result'], $this->subject->fetch(1, 2, 3));
    }

    public function testCloseCursor(): void
    {
        $this->statement->closeCursor()->shouldBeCalled()->willReturn(true);

        self::assertTrue($this->subject->closeCursor());
    }

    public function testRowCount(): void
    {
        $this->statement->rowCount()->shouldBeCalled()->willReturn(3);

        self::assertSame(3, $this->subject->rowCount());
    }

    public function testFetchAll(): void
    {
        $this->statement->fetchAll(1, 2, null)->shouldBeCalled()->willReturn(['result']);

        self::assertSame(['result'], $this->subject->fetchAll(1, 2, null));
    }

    public function testErrorCode(): void
    {
        $this->statement->errorCode()->shouldBeCalled()->willReturn('error');

        self::assertSame('error', $this->subject->errorCode());
    }

    public function testBindParam(): void
    {
        $variable = 'var';
        $this->statement->bindParam('column', $variable, 1, 3)->shouldBeCalled()->willReturn(true);

        self::assertTrue($this->subject->bindParam('column', $variable, 1, 3));
    }

    public function testBindValue(): void
    {
        $this->statement->bindValue('param', 'param value', 3)->shouldBeCalled()->willReturn(true);

        self::assertTrue($this->subject->bindValue('param', 'param value', 3));

        $this->statement->rowCount()->willReturn(5);
        $this->spanFactory->beforeOperation('original sql')->shouldBeCalled();
        $this->spanFactory->afterOperation('original sql', ['param' => 'param value'], $this->username, 5)->shouldBeCalled();

        $this->statement->execute(null)->shouldBeCalled()->willReturn(true);
        self::assertTrue($this->subject->execute());
    }

    public function testFetchColumn(): void
    {
        $this->statement->fetchColumn(3)->shouldBeCalled()->willReturn(['column']);

        self::assertSame(['column'], $this->subject->fetchColumn(3));
    }

    public function testGetIterator(): void
    {
        self::assertSame($this->statement->reveal(), $this->subject->getIterator());
    }

    public function testSetFetchMode(): void
    {
        $this->statement->setFetchMode(3, 'mixed', 6)->shouldBeCalled()->willReturn(true);

        self::assertTrue($this->subject->setFetchMode(3, 'mixed', 6));
    }

    public function testExecute(): void
    {
        $this->spanFactory->beforeOperation($this->sql)->shouldBeCalled();
        $this->statement->execute(['param' => 'param value'])->shouldBeCalled()->willReturn(true);
        $this->statement->rowCount()->shouldBeCalled()->willReturn(4);
        $this->spanFactory->afterOperation($this->sql, ['param' => 'param value'], $this->username, 4);

        self::assertTrue($this->subject->execute(['param' => 'param value']));
    }

    public function testErrorInfo(): void
    {
        $this->statement->errorInfo()->shouldBeCalled()->willReturn(['error info']);

        self::assertSame(['error info'], $this->subject->errorInfo());
    }

    public function testColumnCount(): void
    {
        $this->statement->columnCount()->shouldBeCalled()->willReturn(7);

        self::assertSame(7, $this->subject->columnCount());
    }
}
