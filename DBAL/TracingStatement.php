<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Doctrine\DBAL\Driver\Statement;
use IteratorAggregate;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class TracingStatement implements IteratorAggregate, StatementCombinedResult, WrappingStatement
{
    /**
     * @var Statement<Statement>
     */
    private $statement;
    private string $sql;
    private SpanFactory $spanFactory;
    private ?string $username;
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
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null): array
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
    public function bindValue($param, $value, $type = 2 /* Doctrine\DBAL\ParameterType::STRING */)
    {
        $this->params[$param] = $value;
        return $this->statement->bindValue($param, $value, $type);
    }

    /**
     * @inheritDoc
     */
    public function bindParam($column, &$variable, $type = 2 /* Doctrine\DBAL\ParameterType::STRING */, $length = null)
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
    public function errorInfo(): array
    {
        return $this->statement->errorInfo();
    }

    /**
     * @param array<mixed>|null $params
     * @return bool
     */
    public function execute($params = null): bool
    {
        $this->spanFactory->beforeOperation($this->sql);
        $result = $this->statement->execute($params);
        $this->spanFactory->afterOperation(
            $this->sql,
            $params ?? $this->params,
            $this->username,
            (int) $this->statement->rowCount()
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
     * @return Statement<Statement>
     */
    public function getIterator(): Statement
    {
        return $this->statement;
    }

    public function getWrappedStatement(): Statement
    {
        return $this->statement;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->statement->fetchAllAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        return $this->statement->fetchNumeric();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative()
    {
        return $this->statement->fetchAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne()
    {
        return $this->statement->fetchOne();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->statement->fetchAllNumeric();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->statement->fetchFirstColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        $this->statement->free();
    }
}
