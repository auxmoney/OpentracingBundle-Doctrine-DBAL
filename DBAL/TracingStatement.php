<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Service\Tracing;
use Doctrine\DBAL\Driver\Statement;
use IteratorAggregate;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @implements IteratorAggregate<Statement>
 */
final class TracingStatement implements IteratorAggregate, Statement
{
    /**
     * @var Statement<Statement>
     */
    private $statement;
    private $tracing;
    private $sql;
    private $statementFormatter;

    /**
     * @param Statement<Statement> $statement
     */
    public function __construct(
        Statement $statement,
        Tracing $tracing,
        SQLStatementFormatter $statementFormatter,
        string $sql
    ) {
        $this->statement = $statement;
        $this->tracing = $tracing;
        $this->statementFormatter = $statementFormatter;
        $this->sql = $sql;
    }

    /**
     * @inheritDoc
     */
    public function closeCursor()
    {
        return $this->statement->closeCursor();
    }

    /**
     * @inheritDoc
     */
    public function columnCount()
    {
        return $this->statement->columnCount();
    }

    /**
     * @inheritDoc
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        return $this->statement->setFetchMode($fetchMode, $arg2, $arg3);
    }

    /**
     * @param int|null $fetchMode
     * @param int|null $cursorOrientation
     * @param int|null $cursorOffset
     * @return mixed
     */
    public function fetch($fetchMode = null, $cursorOrientation = null, $cursorOffset = null)
    {
        return $this->statement->fetch($fetchMode = null, $cursorOrientation = null, $cursorOffset = null);
    }

    /**
     * @param int|null $fetchMode
     * @param mixed|null $fetchArgument
     * @param array<mixed>|null $ctorArgs
     * @return array<mixed>
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        return $this->statement->fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null);
    }

    /**
     * @inheritDoc
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->statement->fetchColumn($columnIndex);
    }

    /**
     * @inheritDoc
     */
    public function bindValue($param, $value, $type = null)
    {
        return $this->statement->bindValue($param, $value, $type);
    }

    /**
     * @inheritDoc
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
        return $this->statement->bindParam($column, $variable, $type, $length);
    }

    /**
     * @inheritDoc
     */
    public function errorCode()
    {
        return $this->statement->errorCode();
    }

    /**
     * @return array<mixed>
     */
    public function errorInfo()
    {
        return $this->statement->errorInfo();
    }

    /**
     * @param array<mixed>|null $params
     * @return bool
     */
    public function execute($params = null)
    {
        $this->tracing->startActiveSpan($this->statementFormatter->formatForTracer($this->sql));
        $this->tracing->setTagOfActiveSpan('sql', $this->sql);
        $result = $this->statement->execute($params);
        usleep(5000); // FIXME
        $this->tracing->finishActiveSpan();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function rowCount()
    {
        return $this->statement->rowCount();
    }

    /**
     * @return iterable<Statement>
     */
    public function getIterator()
    {
        return $this->statement;
    }
}
