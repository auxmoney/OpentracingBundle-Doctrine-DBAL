<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

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
    private $sql;
    private $spanFactory;
    private $username;
    /**
     * @var array<mixed>
     */
    private $params = [];

    /**
     * @param Statement<Statement> $statement
     */
    public function __construct(
        Statement $statement,
        SpanFactory $spanFactory,
        string $sql,
        ?string $username
    ) {
        $this->statement = $statement;
        $this->spanFactory = $spanFactory;
        $this->sql = $sql;
        $this->username = $username;
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
     * @param int $cursorOrientation
     * @param int $cursorOffset
     * @return mixed
     */
    public function fetch($fetchMode = null, $cursorOrientation = 0, $cursorOffset = 0)
    {
        return $this->statement->fetch($fetchMode, $cursorOrientation, $cursorOffset);
    }

    /**
     * @param int|null $fetchMode
     * @param int|null $fetchArgument
     * @param array<mixed>|null $ctorArgs
     * @return array<mixed>
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        return $this->statement->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
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
        $this->params[$param] = $value;
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
        $this->spanFactory->beforeOperation($this->sql);
        $result = $this->statement->execute($params);
        $this->spanFactory->afterOperation(
            $this->sql,
            $params ?? $this->params ?? [],
            $this->username,
            $this->statement->rowCount()
        );
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
