<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\DBAL;

use Auxmoney\OpentracingBundle\Internal\Constant;
use Auxmoney\OpentracingBundle\Service\Tracing;
use Doctrine\DBAL\Driver\Connection as DBALDriverConnection;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class TracingDriverConnection implements DBALDriverConnection, WrappingDriverConnection
{
    private $decoratedConnection;
    private $tracing;
    private $spanFactory;
    private $username;

    public function __construct(
        DBALDriverConnection $decoratedConnection,
        Tracing $tracing,
        SpanFactory $spanFactory,
        ?string $username
    ) {
        $this->decoratedConnection = $decoratedConnection;
        $this->tracing = $tracing;
        $this->spanFactory = $spanFactory;
        $this->username = $username;
    }

    /**
     * @param string $prepareString
     * @return TracingStatement
     */
    public function prepare($prepareString)
    {
        $statement = $this->decoratedConnection->prepare($prepareString);
        return new TracingStatement($statement, $this->spanFactory, $prepareString, $this->username);
    }

    /**
     * @return TracingStatement
     */
    public function query()
    {
        $args = func_get_args();
        $parameters = array_slice($args, 1);
        $this->spanFactory->beforeOperation($args[0]);
        $result = $this->decoratedConnection->query(...$args);
        $this->spanFactory->afterOperation($args[0], $parameters, $this->username, $result->rowCount());
        return new TracingStatement($result, $this->spanFactory, $args[0], $this->username);
    }

    /**
     * @param string $input
     * @param int $type
     * @return string
     */
    public function quote($input, $type = 2): string // we do not want a hard dependency on PDO
    {
        return $this->decoratedConnection->quote($input, $type);
    }

    /**
     * @inheritDoc
     */
    public function exec($statement)
    {
        $this->spanFactory->beforeOperation($statement);
        $result = $this->decoratedConnection->exec($statement);
        $this->spanFactory->afterOperation($statement, [], $this->username, $result);
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
        $this->tracing->startActiveSpan('DBAL: TRANSACTION');
        $this->spanFactory->addGeneralTags($this->username);
        $this->tracing->setTagOfActiveSpan(Constant::SPAN_ORIGIN, 'DBAL:transaction');
        $result = $this->decoratedConnection->beginTransaction();
        return is_bool($result) === true ? $result : true;
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        $result = $this->decoratedConnection->commit();
        $this->tracing->setTagOfActiveSpan('db.transaction.end', 'commit');
        $this->tracing->finishActiveSpan();
        return is_bool($result) === true ? $result : true;
    }

    /**
     * @inheritDoc
     */
    public function rollBack()
    {
        $result = $this->decoratedConnection->rollBack();
        $this->tracing->setTagOfActiveSpan('db.transaction.end', 'rollBack');
        $this->tracing->finishActiveSpan();
        return is_bool($result) === true ? $result : true;
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
    public function errorInfo(): array
    {
        return $this->decoratedConnection->errorInfo();
    }

    public function getWrappedConnection(): DBALDriverConnection
    {
        return $this->decoratedConnection;
    }
}
