<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Doctrine\DBAL\Driver\Connection as DBALDriverConnection;
use Doctrine\DBAL\Driver\Statement as DoctrineStatement;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class TracingDriverConnection implements DBALDriverConnection
{
    private $decoratedConnection;
    private $tracing;
    private $statementFormatter;

    public function __construct(
        DBALDriverConnection $decoratedConnection,
        Tracing $tracing,
        SQLStatementFormatter $statementFormatter
    ) {
        $this->decoratedConnection = $decoratedConnection;
        $this->tracing = $tracing;
        $this->statementFormatter = $statementFormatter;
    }

    /**
     * @param string $prepareString
     * @return iterable<DoctrineStatement>
     */
    public function prepare($prepareString)
    {
        $statement = $this->decoratedConnection->prepare($prepareString);
        return new TracingStatement($statement, $this->tracing, $this->statementFormatter, $prepareString);
    }

    /**
     * @return iterable<DoctrineStatement>
     */
    public function query()
    {
        $args = func_get_args();
        $this->tracing->startActiveSpan($this->statementFormatter->formatForTracer($args[0]));
        $this->tracing->setTagOfActiveSpan('sql', $args[0]); # TODO: centralize default tags, extend tags
        $result = $this->decoratedConnection->query(...$args);
        # TODO: handle result tags
        usleep(5000); // FIXME
        $this->tracing->finishActiveSpan();
        return $result;
    }

    /**
     * @param string $input
     * @param int $type
     * @return string
     */
    public function quote($input, $type = 2) // we do not want a hard dependency on PDO
    {
        return $this->decoratedConnection->quote($input, $type);
    }

    /**
     * @inheritDoc
     */
    public function exec($statement)
    {
        $this->tracing->startActiveSpan($this->statementFormatter->formatForTracer($statement));
        $this->tracing->setTagOfActiveSpan('sql', $statement); # TODO
        $result = $this->decoratedConnection->exec($statement);
        # TODO: handle result tags
        usleep(5000); // FIXME
        $this->tracing->finishActiveSpan();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId($name = null)
    {
        return $this->decoratedConnection->lastInsertId($name);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        $this->tracing->startActiveSpan('SQL: (transaction)');
        $this->decoratedConnection->beginTransaction();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        $this->decoratedConnection->commit();
        $this->tracing->setTagOfActiveSpan('db.transaction.end', 'commit');
        $this->tracing->finishActiveSpan();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function rollBack()
    {
        $result = $this->decoratedConnection->rollBack();
        $this->tracing->setTagOfActiveSpan('db.transaction.end', 'rollBack');
        $this->tracing->finishActiveSpan();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function errorCode()
    {
        return $this->decoratedConnection->errorCode();
    }

    /**
     * @return array<mixed>
     */
    public function errorInfo()
    {
        return $this->decoratedConnection->errorInfo();
    }
}
