<?php

declare(strict_types=1);

namespace AWSD\ORM\Tests\Unit\Metadata;

use AWSD\ORM\Core\Metadata\EntityMetadata;
use AWSD\ORM\Core\Metadata\ColumnMetadata;
use AWSD\ORM\Core\Metadata\PrimaryKey;
use AWSD\ORM\Dialect\SqlType;
use PHPUnit\Framework\TestCase;

final class EntityMetadataTest extends TestCase
{
    public function test_it_exposes_schema_table_columns_and_primary_key(): void
    {
        $columns = [
            new ColumnMetadata('id', SqlType::Int, false),
            new ColumnMetadata('email', SqlType::String, false),
            new ColumnMetadata('is_active', SqlType::Bool, false),
        ];

        $pk = new PrimaryKey(['id']);

        $meta = new EntityMetadata(
            schema: 'public',
            table: 'users',
            columns: $columns,
            primaryKey: $pk
        );

        self::assertSame('public', $meta->schema());
        self::assertSame('users', $meta->table());
        self::assertCount(3, $meta->columns());
        self::assertSame($pk, $meta->primaryKey());
    }

    public function test_default_schema_is_public_when_not_provided(): void
    {
        $meta = new EntityMetadata(
            table: 'users',
            columns: [new ColumnMetadata('id', SqlType::Int, false)],
            primaryKey: new PrimaryKey(['id'])
        );

        self::assertSame('public', $meta->schema());
    }

    public function test_find_column_by_name_returns_the_column(): void
    {
        $id = new ColumnMetadata('id', SqlType::Int, false);
        $email = new ColumnMetadata('email', SqlType::String, false);

        $meta = new EntityMetadata(
            table: 'users',
            columns: [$id, $email],
            primaryKey: new PrimaryKey(['id'])
        );

        $found = $meta->findColumn('email');
        self::assertNotNull($found);
        self::assertSame($email, $found);
    }

    public function test_find_column_by_name_returns_null_when_absent(): void
    {
        $meta = new EntityMetadata(
            table: 'users',
            columns: [new ColumnMetadata('id', SqlType::Int, false)],
            primaryKey: new PrimaryKey(['id'])
        );

        self::assertNull($meta->findColumn('unknown'));
    }
}
