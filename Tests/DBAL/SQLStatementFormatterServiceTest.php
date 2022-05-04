<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SQLStatementFormatterService;
use PHPUnit\Framework\TestCase;

class SQLStatementFormatterServiceTest extends TestCase
{
    private SQLStatementFormatterService $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new SQLStatementFormatterService();
    }

    /**
     * @dataProvider provideFormatStrings
     */
    public function testFormatForTracer(string $expected, string $inputString): void
    {
        self::assertSame($expected, $this->subject->formatForTracer($inputString));
    }

    public function provideFormatStrings(): array
    {
        return [
            'SELECT' => ['DBAL: SELECT table_name', 'SELECT id, foo as bar FROM table_name WHERE id > 1 AND foo IS NOT NULL'],
            'DELETE' => ['DBAL: DELETE table_name', 'DELETE FROM table_name WHERE id > 1 AND foo IS NOT NULL'],
            'INSERT' => ['DBAL: INSERT INTO table_name', 'INSERT INTO table_name VALUES (null, "string")'],
            'UPDATE' => ['DBAL: UPDATE table_name', 'UPDATE table_name SET foo = "another string" WHERE id = 6'],
            'fallback' => ['DBAL: ALTER TABLE table_name DROP COLU', 'ALTER TABLE table_name DROP COLUMN str, DROP COLUMN int, DROP COLUMN dec, DROP COLUMN foo, DROP COLUMN bar, DROP COLUMN more'],
        ];
    }

    /**
     * @dataProvider provideBuildSpanOriginStrings
     */
    public function testBuildSpanOrigin(string $expected, string $inputString): void
    {
        self::assertSame($expected, $this->subject->buildSpanOrigin($inputString));
    }

    public function provideBuildSpanOriginStrings(): array
    {
        return [
            'SELECT' => ['DBAL:select', 'SELECT id, foo as bar FROM table_name WHERE id > 1 AND foo IS NOT NULL'],
            'DELETE' => ['DBAL:delete', 'DELETE FROM table_name WHERE id > 1 AND foo IS NOT NULL'],
            'INSERT' => ['DBAL:insert', 'INSERT INTO table_name VALUES (null, "string")'],
            'UPDATE' => ['DBAL:update', 'UPDATE table_name SET foo = "another string" WHERE id = 6'],
            'fallback' => ['DBAL:alter', 'ALTER TABLE table_name DROP COLUMN str, DROP COLUMN int, DROP COLUMN dec, DROP COLUMN foo, DROP COLUMN bar, DROP COLUMN more'],
        ];
    }
}
