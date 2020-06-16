<?php

declare(strict_types=1);

namespace Auxmoney\OpentracingDoctrineDBALBundle\Tests\DBAL;

use Auxmoney\OpentracingDoctrineDBALBundle\DBAL\SQLStatementFormatterService;
use PHPUnit\Framework\TestCase;

class SQLStatementFormatterServiceTest extends TestCase
{
    /** @var SQLStatementFormatterService */
    private $subject;

    public function setUp()
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
     * @dataProvider provideExtractStrings
     */
    public function testExtractOperation(string $expected, string $inputString): void
    {
        self::assertSame($expected, $this->subject->extractOperation($inputString));
    }

    public function provideExtractStrings(): array
    {
        return [
            'SELECT' => ['select', 'SELECT id, foo as bar FROM table_name WHERE id > 1 AND foo IS NOT NULL'],
            'DELETE' => ['delete', 'DELETE FROM table_name WHERE id > 1 AND foo IS NOT NULL'],
            'INSERT' => ['insert', 'INSERT INTO table_name VALUES (null, "string")'],
            'UPDATE' => ['update', 'UPDATE table_name SET foo = "another string" WHERE id = 6'],
            'fallback' => ['alter', 'ALTER TABLE table_name DROP COLUMN str, DROP COLUMN int, DROP COLUMN dec, DROP COLUMN foo, DROP COLUMN bar, DROP COLUMN more'],
        ];
    }
}
