<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SQLSpanFactory;
use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SQLStatementFormatter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class SQLSpanFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $statementFormatter;
    private $tracing;
    private $tagFullStatement;
    private $tagParameters;
    private $tagRowCount;
    private $tagUser;
    /** @var SQLSpanFactory */
    private $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->statementFormatter = $this->prophesize(SQLStatementFormatter::class);
        $this->tracing = $this->prophesize(Tracing::class);
        $this->tagFullStatement = 'true';
        $this->tagParameters = '1';
        $this->tagRowCount = 'on';
        $this->tagUser = 'yes';

        $this->subject = new SQLSpanFactory(
            $this->statementFormatter->reveal(),
            $this->tracing->reveal(),
            $this->tagFullStatement,
            $this->tagParameters,
            $this->tagRowCount,
            $this->tagUser
        );
    }

    public function testBeforeOperation(): void
    {
        $this->statementFormatter->formatForTracer('a SQL statement')->willReturn('SQL');

        $this->tracing->startActiveSpan('SQL')->shouldBeCalled();

        $this->subject->beforeOperation('a SQL statement');
    }

    public function testAddGeneralTagsComplete(): void
    {
        $this->tracing->setTagOfActiveSpan('span.kind', 'client')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('span.source', 'DBAL')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.type', 'sql')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.user', 'username')->shouldBeCalled();

        $this->subject->addGeneralTags('username');
    }

    public function testAddGeneralTagsMinimal(): void
    {
        $this->subject =  new SQLSpanFactory(
            $this->statementFormatter->reveal(),
            $this->tracing->reveal(),
            'false',
            '0',
            'off',
            'no'
        );

        $this->tracing->setTagOfActiveSpan('span.kind', 'client')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('span.source', 'DBAL')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.type', 'sql')->shouldBeCalled();

        $this->subject->addGeneralTags('username');
    }

    public function testAfterOperationComplete(): void
    {
        $this->tracing->setTagOfActiveSpan('span.kind', 'client')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('span.source', 'DBAL')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.type', 'sql')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.user', 'username')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.statement', 'a SQL statement')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.parameters', '{"param":"param value"}')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.row_count', 5)->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('auxmoney-opentracing-bundle.span-origin', 'a')->shouldBeCalled();
        $this->tracing->finishActiveSpan()->shouldBeCalled();

        $this->statementFormatter->buildSpanOrigin('a SQL statement')->willReturn('a');

        $this->subject->afterOperation('a SQL statement', ['param' => 'param value'], 'username', 5);
    }

    public function testAfterOperationMinimal(): void
    {
        $this->subject =  new SQLSpanFactory(
            $this->statementFormatter->reveal(),
            $this->tracing->reveal(),
            'false',
            '0',
            'off',
            'no'
        );

        $this->tracing->setTagOfActiveSpan('span.kind', 'client')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('span.source', 'DBAL')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('db.type', 'sql')->shouldBeCalled();
        $this->tracing->setTagOfActiveSpan('auxmoney-opentracing-bundle.span-origin', 'a')->shouldBeCalled();
        $this->tracing->finishActiveSpan()->shouldBeCalled();

        $this->statementFormatter->buildSpanOrigin('a SQL statement')->willReturn('a');

        $this->subject->afterOperation('a SQL statement', ['param' => 'param value'], 'username', 5);
    }
}
