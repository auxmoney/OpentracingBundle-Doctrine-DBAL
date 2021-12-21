<?php

declare(strict_types=1);

namespace App\Command;

use Auxmoney\OpentracingBundle\Internal\Opentracing;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use const OpenTracing\Formats\TEXT_MAP;

class TestConnectionAPICommand extends Command
{
    private $connection;
    private $opentracing;

    public function __construct(Connection $connection, Opentracing $opentracing)
    {
        parent::__construct('test:doctrine:dbal-connection-api');

        $this->connection = $connection;
        $this->opentracing = $opentracing;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 0);

        Assert::eq($this->connection->insert('test_table', ['str' => 'a'], [ParameterType::STRING]), 1);
        Assert::eq($this->connection->fetchAll('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL')[0]['COUNT(*)'], 1);
        Assert::eq($this->connection->fetchAllAssociative('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL')[0]['COUNT(*)'], 1);

        Assert::eq($this->connection->insert('test_table', ['str' => 'b'], [ParameterType::STRING]), 1);
        Assert::eq($this->connection->fetchColumn('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 2);
        Assert::eq($this->connection->fetchOne('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL'), 2);

        Assert::eq($this->connection->update('test_table', ['str' => null], ['str' => 'a'], [ParameterType::STRING, ParameterType::STRING]), 1);
        Assert::eq($this->connection->fetchArray('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL')[0], 1);
        Assert::eq($this->connection->fetchAllNumeric('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL')[0][0], 1);

        $id = $this->connection->executeQuery('SELECT id FROM test_table WHERE str IS NOT NULL')->fetchColumn();
        Assert::eq($this->connection->delete('test_table', ['id' => $id], [ParameterType::INTEGER]), 1);
        Assert::eq($this->connection->fetchAssoc('SELECT COUNT(*) FROM test_table WHERE str IS NOT NULL')['COUNT(*)'], 0);

        Assert::eq($this->connection->exec('UPDATE test_table SET str = NULL WHERE str IS NOT NULL'), 0);

        $carrier = [];
        $this->opentracing->getTracerInstance()->inject($this->opentracing->getTracerInstance()->getActiveSpan()->getContext(), TEXT_MAP, $carrier);
        $output->writeln(current($carrier));

        return 0;
    }
}
