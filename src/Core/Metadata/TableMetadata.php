<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata;

use AWSD\ORM\Core\Metadata\Validator\TableMetadataValidator;
use AWSD\ORM\Utils\Normalize;

/**
 * Immutable value object carrying the SQL table identity for an entity.
 *
 * Responsibilities:
 * - Hold both the raw inputs (as provided by attributes/factory) and the normalized
 *   names used by the ORM.
 * - Apply upstream normalization (trim → snake_case → lowercase ASCII) for
 *   schema and table names.
 * - Validate normalized identifiers against PostgreSQL *unquoted* identifier rules
 *   (non-empty, <= 63 chars, pattern).
 *
 * Notes:
 * - No SQL quoting happens here; quoting is dialect-specific and must be handled
 *   by the SQL renderer.
 * - Normalization is performed via {@see Normalize}; validation via
 *   {@see TableMetadataValidator}.
 */
final class TableMetadata
{
  /**
   * Raw (un-normalized) table name as provided by the caller (e.g., attribute).
   * @var string
   */
  private readonly string $rawTableName;

  /**
   * Normalized table name (lowercase snake_case, ASCII), validated.
   * @var string
   */
  private readonly string $tableName;

  /**
   * Raw (un-normalized) schema name as provided by the caller.
   * @var string
   */
  private readonly string $rawSchemaName;

  /**
   * Normalized schema name (lowercase snake_case, ASCII), validated.
   * @var string
   */
  private readonly string $schemaName;

  /**
   * @param string $name   Raw table name (will be trimmed and normalized).
   * @param string $schema Raw schema name (default "public"; will be trimmed and normalized).
   *
   * @throws \InvalidArgumentException If, after normalization, either identifier is empty,
   *                                   exceeds PostgreSQL's 63-char limit, or fails the
   *                                   unquoted identifier pattern.
   */
  public function __construct(string $name, string $schema = 'public')
  {
    $this->rawTableName  = Normalize::string($name);
    $this->rawSchemaName = Normalize::string($schema);
    $this->tableName  = Normalize::toSnakeCaseAscii($this->rawTableName);
    $this->schemaName = Normalize::toSnakeCaseAscii($this->rawSchemaName);
    $this->validate();
  }

  /**
   * Raw (un-normalized) table name as received.
   * @return string
   */
  public function rawTableName(): string
  {
    return $this->rawTableName;
  }

  /**
   * Normalized and validated table name (lowercase snake_case).
   * @return string
   */
  public function tableName(): string
  {
    return $this->tableName;
  }

  /**
   * Raw (un-normalized) schema name as received.
   * @return string
   */
  public function rawSchemaName(): string
  {
    return $this->rawSchemaName;
  }

  /**
   * Normalized and validated schema name (lowercase snake_case).
   * @return string
   */
  public function schemaName(): string
  {
    return $this->schemaName;
  }

  /**
   * Convenience: unquoted fully-qualified name using normalized parts.
   * Example: "public.users" (without SQL quotes).
   *
   * @return string schema.table using normalized identifiers
   */
  public function schemaTable(): string
  {
    return $this->schemaName . '.' . $this->tableName;
  }

  /**
   * Normalize inputs and validate identifiers (constructor helper).
   *
   * @throws \InvalidArgumentException See constructor.
   */
  private function validate(): void
  {
    TableMetadataValidator::validateTableName($this->tableName);
    TableMetadataValidator::validateSchemaName($this->schemaName);
  }
}
