<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\Functional;

use Auxmoney\OpentracingBundle\Tests\Functional\JaegerConsoleFunctionalTest;
use Symfony\Component\Process\Process;

class FunctionalTest extends JaegerConsoleFunctionalTest
{
    /**
     * @dataProvider provideDBALProjects
     */
    public function testDbalConnectionApi(string $project, bool $withORM): void
    {
        $this->setupTestProjectDbal($project, $withORM);

        $this->assertSpans('dbal-connection-api', 15);
    }

    /**
     * @dataProvider provideDBALProjects
     */
    public function testDbalPreparedStatements(string $project, bool $withORM): void
    {
        $this->setupTestProjectDbal($project, $withORM);

        $this->assertSpans('dbal-prepared-statements', 14);
    }

    /**
     * @dataProvider provideDBALProjects
     */
    public function testDbalQueryBuilder(string $project, bool $withORM): void
    {
        $this->setupTestProjectDbal($project, $withORM);

        $this->assertSpans('dbal-query-builder', 13);
    }

    /**
     * @dataProvider provideDBALProjects
     */
    public function testDbalTransactions(string $project, bool $withORM): void
    {
        $this->setupTestProjectDbal($project, $withORM);

        $this->assertSpans('dbal-transactions', 15);
    }

    public function testOrmEntity(): void
    {
        $this->setupTestProjectDbal('orm-em', true);

        $this->assertSpans('orm-entity', 14);
    }

    public function testOrmQueryBuilder(): void
    {
        $this->setupTestProjectDbal('orm-em', true);

        $this->assertSpans('orm-query-builder', 17);
    }

    public function provideDBALProjects(): array
    {
        return [
            'without wrapper class' => ['dbal-standard', false],
            'with wrapper class' => ['dbal-wrapper', false],
            'orm + entitymanager' => ['orm-em', true],
        ];
    }

    private function assertSpans(string $command, int $spanCount): void
    {
        $process = new Process(['symfony', 'console', 'test:doctrine:' . $command], self::BUILD_TESTPROJECT);
        $process->mustRun();
        $output = $process->getOutput();
        $traceId = substr($output, 0, strpos($output, ':'));
        self::assertNotEmpty($traceId);

        $spans = $this->getSpansFromTrace($this->getTraceFromJaegerAPI($traceId));
        self::assertCount($spanCount, $spans);

        $traceAsYAML = $this->getSpansAsYAML($spans, '[].{operationName: operationName, startTime: startTime, spanID: spanID, references: references, tags: tags[?key==\'db.statement\' || key==\'db.parameters\' || key==\'db.row_count\' || key==\'db.user\' || key==\'db.transaction.end\' || key==\'command.exit-code\' || key==\'auxmoney-opentracing-bundle.span-origin\'].{key: key, value: value}}');

        /*
         * TODO: not sure why, but from 8.1 on, some component actually preserves the strict types from the jaeger response, e.g. response contained tags like
         * > {"1": 2}
         *
         * this was parsed up until < 8.1 into:
         * > {"1": "2"}
         *
         * this is parsed >= 8.1 into:
         * > {"1": 2}
         *
         * may be deleted / fixed as soon as root cause of this behaviour is found / vendors are fixed
         */
        $strictSuffix = '';
        if (PHP_VERSION_ID >= 80100) {
            $strictSuffix = '.strict';
        }

        self::assertStringEqualsFile(__DIR__ . '/FunctionalTest.' . $command . $strictSuffix . '.expected.yaml', $traceAsYAML);
    }

    private function setupTestProjectDbal(string $project, bool $withORM): void
    {
        if ($withORM) {
            $this->runInTestProject(['git', 'reset', '--hard', 'reset.orm']);
        } else {
            $this->gitResetTestProject();
        }
        $this->runInTestProject(['git', 'clean', '-df']);

        $this->setUpTestProject($project);

        $this->runInTestProject(['symfony', 'console', 'doctrine:database:create']);

        if ($withORM) {
            $this->runInTestProject(['symfony', 'console', 'doctrine:schema:create']);
        } else {
            $this->runInTestProject(['symfony', 'console', 'test:doctrine:setup-table']);
        }
    }

    protected function setUpTestProject(string $projectSetup): void
    {
        $this->copyTestProjectFiles($projectSetup);

        $this->composerDumpAutoload();
        $this->consoleCacheClear();
    }

    protected function tearDown(): void
    {
        $this->runInTestProject(['symfony', 'console', 'doctrine:database:drop', '--force']);

        $this->gitResetTestProject();
        $this->dockerStopJaeger();
    }
}
