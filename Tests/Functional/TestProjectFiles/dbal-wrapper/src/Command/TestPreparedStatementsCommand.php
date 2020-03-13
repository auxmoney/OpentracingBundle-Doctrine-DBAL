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

class TestPreparedStatementsCommand extends Command
{
    private $connection;
    private $opentracing;

    public function __construct(Connection $connection, Opentracing $opentracing)
    {
        parent::__construct('test:doctrine:dbal-prepared-statements');

        $this->connection = $connection;
        $this->opentracing = $opentracing;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Assert::isInstanceOf($this->connection, TestWrapper::class);
        $selectCount = $this->connection->executeQuery('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL');
        Assert::eq($selectCount->fetch()['COUNT(*)'], 0);

        $insert = $this->connection->prepare('INSERT INTO test_table VALUES (null, :str)');

        $insert->execute(['str' => 'a']);
        Assert::eq($insert->rowCount(), 1);
        $selectCount->execute();
        Assert::eq($selectCount->fetchAll()[0]['COUNT(*)'], 1);

        $insert->execute(['str' => 'b']);
        Assert::eq($insert->rowCount(), 1);
        $selectCount->execute();
        Assert::eq($selectCount->fetchColumn(), 2);

        Assert::eq($this->connection->executeUpdate('UPDATE test_table SET str = :new WHERE str = :original', ['new' => null, 'original' => 'a']), 1);
        $selectCount->execute();
        Assert::eq($selectCount->fetchColumn(), 1);

        Assert::eq($this->connection->executeUpdate('DELETE FROM test_table WHERE str IS NOT NULL'), 1);
        $selectCount->execute();
        Assert::eq($selectCount->fetchColumn(), 0);

        Assert::eq($this->connection->exec('UPDATE test_table SET str = NULL WHERE str IS NOT NULL'), 0);
        Assert::eq($this->connection->getInsertCount(), 0);
        Assert::eq($this->connection->getExecuteQueryCount(), 1);

        $carrier = [];
        $this->opentracing->getTracerInstance()->inject($this->opentracing->getTracerInstance()->getActiveSpan()->getContext(), TEXT_MAP, $carrier);
        $output->writeln(current($carrier));

        return 0;
    }
}
