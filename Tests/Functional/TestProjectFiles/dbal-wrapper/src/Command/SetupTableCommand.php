<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupTableCommand extends Command
{
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct('test:doctrine:setup-table');

        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->connection->exec('create table test_table (
            id INTEGER
            constraint test_table_pk
            primary key autoincrement,
            str TEXT
        );
        ');

        return 0;
    }
}
