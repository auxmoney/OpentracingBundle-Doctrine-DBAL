<?php

declare(strict_types=1);

namespace App\Wrapper;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;

class TestWrapper extends Connection
{
    private $insertCount = 0;
    private $executeQueryCount = 0;

    public function insert($tableExpression, array $data, array $types = [])
    {
        $this->insertCount++;
        return parent::insert($tableExpression, $data, $types);
    }

    public function executeQuery($query, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        $this->executeQueryCount++;
        return parent::executeQuery($query, $params, $types, $qcp);
    }

    public function getInsertCount(): int
    {
        return $this->insertCount;
    }

    public function getExecuteQueryCount(): int
    {
        return $this->executeQueryCount;
    }
}
