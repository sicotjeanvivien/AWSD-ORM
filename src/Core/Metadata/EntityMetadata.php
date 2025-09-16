<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata;

use AWSD\ORM\Utils\Normalize;

final class EntityMetadata
{

  private readonly TableMetadata $tableMetadata;
  private readonly array $columnsMetadata;
  private readonly PrimaryKeyMetadata $primaryKeyMetadata;

  public function __construct(TableMetadata $table, array $columns, PrimaryKeyMetadata $primaryKey)
  {
    $this->initalizeTableMetadata($table);
    $this->initalizeColumnsMetadata($columns);
    $this->initalizePrimaryKeyMetadata($primaryKey);
  }

  public function tableMetadata(): TableMetadata
  {
    return $this->tableMetadata;
  }

  public function columns(): array
  {
    return $this->columnsMetadata;
  }

  public function primaryKey(): PrimaryKeyMetadata
  {
    return $this->primaryKeyMetadata;
  }

  private function initalizeTableMetadata(TableMetadata $table): void
  {
    $this->tableMetadata = $table;
  }

  private function initalizeColumnsMetadata(array $columns): void
  {
    // $this->validateColumsMetadata($columns);

    // $this->assignColumnsMetadata($columns);
  }

  private function validateColumsMetadata(array $columns): void
  {
    // $cols = self::validateColumns($columns);
    // $index = self::buildColumnIndex($cols);
  }


  private function initalizePrimaryKeyMetadata(PrimaryKeyMetadata $primaryKey): void
  {
    // $this->validatePrimaryKeyMetadata($primaryKey);
    // $this->assignPrimaryKeyMetadata($primaryKey);
  }
}
