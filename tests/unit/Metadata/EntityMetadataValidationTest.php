<?php

declare(strict_types=1);

namespace AWSD\ORM\Tests\Unit\Metadata;

use AWSD\ORM\Core\Metadata\EntityMetadata;
use AWSD\ORM\Core\Metadata\ColumnMetadata;
use AWSD\ORM\Core\Metadata\PrimaryKey;
use AWSD\ORM\Dialect\SqlType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EntityMetadataValidationTest extends TestCase
{
    public function test_table_name_must_be_non_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name must be a non-empty string');

        new EntityMetadata(
            table: '',
            columns: [new ColumnMetadata('id', SqlType::Int, false)],
            primaryKey: new PrimaryKey(['id'])
        );
    }

    public function test_columns_must_have_unique_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate column name: id');

        new EntityMetadata(
            table: 'users',
            columns: [
                new ColumnMetadata('id', SqlType::Int, false),
                new ColumnMetadata('id', SqlType::Int, false), // duplicate
            ],
            primaryKey: new PrimaryKey(['id'])
        );
    }

    public function test_primary_key_must_not_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Primary key must contain at least one column');

        new EntityMetadata(
            table: 'users',
            columns: [new ColumnMetadata('id', SqlType::Int, false)],
            primaryKey: new PrimaryKey([]) // empty PK
        );
    }

    public function test_primary_key_columns_must_exist_in_columns_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Primary key references undefined column: user_id');

        new EntityMetadata(
            table: 'users',
            columns: [new ColumnMetadata('id', SqlType::Int, false)],
            primaryKey: new PrimaryKey(['user_id']) // not defined
        );
    }

    public function test_composite_primary_key_is_supported(): void
    {
        $meta = new EntityMetadata(
            table: 'user_tenants',
            columns: [
                new ColumnMetadata('tenant_id', SqlType::Int, false),
                new ColumnMetadata('user_id', SqlType::Int, false),
                new ColumnMetadata('role', SqlType::String, false),
            ],
            primaryKey: new PrimaryKey(['tenant_id', 'user_id'])
        );

        $pk = $meta->primaryKey();
        self::assertSame(['tenant_id', 'user_id'], $pk->columns());
    }
}
