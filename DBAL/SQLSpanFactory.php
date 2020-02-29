<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Auxmoney\OpentracingDoctrineDBALBundle\OpentracingDoctrineDBALBundle;
use const OpenTracing\Tags\DATABASE_STATEMENT;
use const OpenTracing\Tags\DATABASE_TYPE;
use const OpenTracing\Tags\DATABASE_USER;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

class SQLSpanFactory implements SpanFactory
{
    private $statementFormatter;
    private $tracing;
    private $tagFullStatement;
    private $tagParameters;
    private $tagRowCount;
    private $tagUser;

    public function __construct(
        SQLStatementFormatter $statementFormatter,
        Tracing $tracing,
        string $tagFullStatement,
        string $tagParameters,
        string $tagRowCount,
        string $tagUser
    ) {
        $this->statementFormatter = $statementFormatter;
        $this->tracing = $tracing;
        $this->tagFullStatement = filter_var($tagFullStatement, FILTER_VALIDATE_BOOLEAN);
        $this->tagParameters = filter_var($tagParameters, FILTER_VALIDATE_BOOLEAN);
        $this->tagRowCount = filter_var($tagRowCount, FILTER_VALIDATE_BOOLEAN);
        $this->tagUser = filter_var($tagUser, FILTER_VALIDATE_BOOLEAN);
    }

    public function beforeOperation(string $sql): void
    {
        $this->tracing->startActiveSpan($this->statementFormatter->formatForTracer($sql));
    }

    public function afterOperation(string $sql, array $parameters, ?string $username, int $affectedRowCount): void
    {
        $this->addGeneralTags($username);
        if ($this->tagFullStatement) {
            $this->tracing->setTagOfActiveSpan(DATABASE_STATEMENT, $sql);
        }
        if ($this->tagParameters) {
            $this->tracing->setTagOfActiveSpan('db.parameters', json_encode($parameters));
        }
        if ($this->tagRowCount) {
            $this->tracing->setTagOfActiveSpan('db.row_count', $affectedRowCount);
        }
        $this->tracing->finishActiveSpan();
    }

    public function addGeneralTags(?string $username): void
    {
        $this->tracing->setTagOfActiveSpan(SPAN_KIND, SPAN_KIND_RPC_CLIENT);
        $this->tracing->setTagOfActiveSpan(
            'span.source',
            OpentracingDoctrineDBALBundle::AUXMONEY_OPENTRACING_BUNDLE_TYPE
        );
        $this->tracing->setTagOfActiveSpan(DATABASE_TYPE, 'sql');
        if ($this->tagUser) {
            $this->tracing->setTagOfActiveSpan(DATABASE_USER, $username);
        }
    }
}
