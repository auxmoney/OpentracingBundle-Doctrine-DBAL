<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\Functional;

use Auxmoney\OpentracingBundle\Tests\Functional\JaegerFunctionalTest;

class FunctionalTest extends JaegerFunctionalTest
{
    public function testStandard(): void
    {
        $this->setUpTestProject('dbal-standard');

        $this->runInTestProject(['symfony', 'console', 'test:doctrine:setup-table']);
        $this->runInTestProject(['symfony', 'console', 'test:doctrine:tracing']);
        self::assertSame(true, true);
    }
}
