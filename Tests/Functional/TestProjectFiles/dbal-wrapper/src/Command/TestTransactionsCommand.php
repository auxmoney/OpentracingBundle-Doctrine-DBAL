<?php

declare(strict_types=1);

namespace App\Command;

use App\Wrapper\TestWrapper;
use Auxmoney\OpentracingBundle\Internal\Opentracing;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use const OpenTracing\Formats\TEXT_MAP;

class TestTransactionsCommand extends Command
{
    private $connection;
    private $opentracing;

    public function __construct(Connection $connection, Opentracing $opentracing)
    {
        parent::__construct('test:doctrine:dbal-transactions');

        $this->connection = $connection;
        $this->opentracing = $opentracing;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Assert::isInstanceOf($this->connection, TestWrapper::class);
        $this->connection->beginTransaction();
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 0);
        $this->connection->beginTransaction();
        Assert::eq($this->connection->insert('test_table', ['str' => 'a']), 1);
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 1);
        $this->connection->commit();
        $id = $this->connection->executeQuery('SELECT id FROM test_table WHERE str IS NOT NULL')->fetchColumn();
        Assert::eq($this->connection->update('test_table', ['str' => null], ['id' => $id]), 1);
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 0);
        $this->connection->commit();

        $this->connection->setAutoCommit(false);
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 0);
        $this->connection->beginTransaction();
        Assert::eq($this->connection->insert('test_table', ['str' => 'a']), 1);
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 1);
        $this->connection->rollBack();
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 0);
        $this->connection->setAutoCommit(true);

        Assert::eq($this->connection->exec('UPDATE test_table SET str = NULL WHERE str IS NOT NULL'), 0);
        Assert::eq($this->connection->getInsertCount(), 2);
        Assert::eq($this->connection->getExecuteQueryCount(), 7);

        $carrier = [];
        $this->opentracing->getTracerInstance()->inject($this->opentracing->getTracerInstance()->getActiveSpan()->getContext(), TEXT_MAP, $carrier);
        $output->writeln(current($carrier));

        return 0;
    }
}
