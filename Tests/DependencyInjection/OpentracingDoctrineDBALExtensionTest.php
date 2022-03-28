<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DependencyInjection;

use Auxmoney\OpentracingDoctrineDBALBundle\DependencyInjection\OpentracingDoctrineDBALExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OpentracingDoctrineDBALExtensionTest extends TestCase
{
    private OpentracingDoctrineDBALExtension $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new OpentracingDoctrineDBALExtension();
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $this->subject->load([], $container);

        self::assertArrayHasKey('auxmoney_opentracing.doctrine.dbal.connection_factory', $container->getDefinitions());
    }
}
