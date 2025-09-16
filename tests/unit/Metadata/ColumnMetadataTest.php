<?php

declare(strict_types=1);

namespace AWSD\ORM\Tests\Unit\Metadata;

use AWSD\ORM\Core\Metadata\ColumnMetadata;
use AWSD\ORM\Dialect\SqlType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ColumnMetadataTest extends TestCase
{
    public function test_column_requires_non_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column name must be a non-empty string');

        new ColumnMetadata('', SqlType::Int, false);
    }

    public function test_column_carries_declared_type_and_nullability(): void
    {
        $col = new ColumnMetadata('email', SqlType::String, false);

        self::assertSame('email', $col->name());
        self::assertSame(SqlType::String, $col->type());
        self::assertFalse($col->nullable());
    }

    public function test_nullable_column_is_allowed(): void
    {
        $col = new ColumnMetadata('middle_name', SqlType::String, true);
        self::assertTrue($col->nullable());
    }
}
