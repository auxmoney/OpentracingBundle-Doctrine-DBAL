<?php

declare(strict_types=1);

namespace App\Command;

use Auxmoney\OpentracingBundle\Internal\Opentracing;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use const OpenTracing\Formats\TEXT_MAP;

class TestQueryBuilderCommand extends Command
{
    private $connection;
    private $opentracing;

    public function __construct(Connection $connection, Opentracing $opentracing)
    {
        parent::__construct('test:doctrine:dbal-query-builder');

        $this->connection = $connection;
        $this->opentracing = $opentracing;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $selectBuilder = $this->connection->createQueryBuilder();
        $selectBuilder->select('COUNT(*)')
            ->from('test_table')
            ->where('str IS NOT NULL');
        Assert::eq($selectBuilder->execute()->fetchColumn(), 0);

        $insertBuilder = $this->connection->createQueryBuilder();
        $insertBuilder = $insertBuilder->insert('test_table')
            ->values(['str' => ':str'])
            ->setParameter('str', 'a');
        Assert::eq($insertBuilder->execute(), 1);
        Assert::eq($selectBuilder->execute()->fetchColumn(), 1);

        $insertBuilder = $insertBuilder->setParameter('str', 'b');
        Assert::eq($insertBuilder->execute(), 1);
        Assert::eq($selectBuilder->execute()->fetchColumn(), 2);

        $idSelectBuilder = $this->connection->createQueryBuilder();
        $idSelectBuilder = $idSelectBuilder->select('id')
            ->from('test_table')
            ->where('str = :str')
            ->setParameters(['str' => 'a']);
        $id = $idSelectBuilder->execute()->fetchColumn();
        $updateBuilder = $this->connection->createQueryBuilder();
        $updateBuilder->update('test_table')
            ->set('str', ':str')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->setParameter('str', null);
        Assert::eq($updateBuilder->execute(), 1);
        Assert::eq($selectBuilder->execute()->fetchColumn(), 1);

        $id = $idSelectBuilder->setParameters(['str' => 'b'])->execute()->fetchColumn();
        $deleteBuilder = $this->connection->createQueryBuilder();
        $deleteBuilder->delete('test_table')
            ->where('id = :id')
            ->setParameter('id', $id);
        Assert::eq($deleteBuilder->execute(), 1);
        Assert::eq($selectBuilder->execute()->fetchOne(), 0);

        Assert::eq($this->connection->exec('UPDATE test_table SET str = NULL WHERE str IS NOT NULL'), 0);

        $carrier = [];
        $this->opentracing->getTracerInstance()->inject($this->opentracing->getTracerInstance()->getActiveSpan()->getContext(), TEXT_MAP, $carrier);
        $output->writeln(current($carrier));

        return 0;
    }
}
