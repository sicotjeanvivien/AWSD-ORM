<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata\Validator;

use AWSD\ORM\Dialect\SqlType;
use AWSD\ORM\Core\DefaultValueMetadata;
use InvalidArgumentException;

/**
 * ColumnMetadataValidator
 *
 * V1 — PostgreSQL-first.
 * Validates the *shape* of column attributes (name, length, precision/scale) independently
 * from default semantics (handled by DefaultValueMetadataValidator).
 *
 * Scope/Design:
 * - No normalization is performed here (e.g., scale=null is NOT rewritten to 0).
 * - VARCHAR: requires a positive length; other types must not define length.
 * - NUMERIC: requires precision >= 1 and 0 <= scale <= precision; other types must not
 *            define precision/scale.
 * - Column name checks: non-empty, <= 63 bytes (PostgreSQL identifiers limit), and
 *   not in a minimal reserved-word list.
 *
 * For default value/type compatibility rules, call
 * DefaultValueMetadataValidator::validate().
 */
final class ColumnMetadataValidator
{
  /**
   * Minimal reserved words list (PostgreSQL). Not exhaustive.
   * Extend/replace with dialect capabilities if needed.
   *
   * @var string[]
   */
  private const RESERVED_WORDS = [
    'select',
    'from',
    'where',
    'table',
    'user',
    'order',
    'group',
    'limit',
    'insert',
    'update',
    'delete',
    'join',
    'having',
    'into',
  ];

  /**
   * Validate a logical column name.
   *
   * Rules:
   * - Must be non-empty.
   * - Must not exceed 63 bytes (PostgreSQL identifier limit). Assumes upstream ASCII normalization,
   *   otherwise ensure the name is converted to an identifier-safe ASCII form before measurement.
   *
   * @param string $name Logical, unquoted column name.
   * @throws InvalidArgumentException If the name is empty or exceeds the identifier length limit.
   * @return void
   */
  public static function nameValidator(string $name): void
  {
    if ($name === '') {
      throw new InvalidArgumentException('Column name cannot be empty.');
    }
    if (strlen($name) > 63) {
      throw new InvalidArgumentException('Column name exceeds PostgreSQL identifier limit (63 bytes).');
    }
  }

  /**
   * Ensure a column name is not a (minimal) reserved SQL keyword.
   *
   * Note: the list is intentionally minimal for V1 and not exhaustive.
   *
   * @param string $name Logical, unquoted column name.
   * @throws InvalidArgumentException If the name is a reserved keyword in the minimal list.
   * @return void
   */
  public static function reservedWordValidator(string $name): void
  {
    if (in_array(strtolower($name), self::RESERVED_WORDS, true)) {
      throw new InvalidArgumentException("Column name '{$name}' is a reserved SQL keyword.");
    }
  }

  /**
   * Validate VARCHAR length (> 0).
   *
   * @param int|null $length Length value (only meaningful for VARCHAR).
   * @throws InvalidArgumentException If length is null or not strictly positive.
   * @return void
   */
  public static function varcharValidator(?int $length): void
  {
    if ($length === null || $length <= 0) {
      throw new InvalidArgumentException('VARCHAR requires a positive length.');
    }
  }

  /**
   * Ensure non-VARCHAR types do not define a length attribute.
   *
   * @param int|null $length Length value.
   * @throws InvalidArgumentException If a non-VARCHAR type defines a length.
   * @return void
   */
  public static function notVarcharValidator(?int $length): void
  {
    if ($length !== null) {
      throw new InvalidArgumentException('Length is only allowed for VARCHAR.');
    }
  }

  /**
   * Validate NUMERIC precision/scale (no normalization here).
   *
   * Rules:
   * - precision >= 1
   * - 0 <= scale <= precision (when scale is null, treat as 0 for validation purposes only)
   *
   * @param int|null $precision Precision value (only for NUMERIC).
   * @param int|null $scale     Scale value (only for NUMERIC).
   * @throws InvalidArgumentException If precision/scale do not satisfy the constraints.
   * @return void
   */
  public static function numericValidator(?int $precision, ?int $scale): void
  {
    if ($precision === null || $precision < 1) {
      throw new InvalidArgumentException('NUMERIC requires a precision >= 1.');
    }
    $s = $scale ?? 0;
    if ($s < 0 || $s > $precision) {
      throw new InvalidArgumentException('NUMERIC scale must satisfy 0 <= scale <= precision.');
    }
  }

  /**
   * Ensure non-NUMERIC types do not define precision/scale.
   *
   * @param int|null $precision Precision value.
   * @param int|null $scale     Scale value.
   * @throws InvalidArgumentException If precision/scale are present for a non-NUMERIC type.
   * @return void
   */
  public static function notNumericValidator(?int $precision, ?int $scale): void
  {
    if ($precision !== null || $scale !== null) {
      throw new InvalidArgumentException('Precision/scale are only allowed for NUMERIC.');
    }
  }

  /**
   * Delegate default-value/type compatibility checks to DefaultValueMetadataValidator.
   *
   * This method does not modify values and only forwards parameters to the default validator.
   *
   * @param SqlType              $type       Column SQL type (PG-first).
   * @param DefaultValueMetadata $default    Default value descriptor (kind + payload).
   * @param int|null             $length     Column length (only meaningful for VARCHAR).
   * @param int|null             $precision  Column precision (only meaningful for NUMERIC).
   * @param int|null             $scale      Column scale (only meaningful for NUMERIC).
   *
   * @throws InvalidArgumentException If the default is incompatible with the given type or shape.
   * @return void
   */
  public static function defaultCompatibilityByType(
    SqlType $type,
    DefaultValueMetadata $default,
    ?int $length,
    ?int $precision,
    ?int $scale
  ): void {
    DefaultValueMetadataValidator::validate($type, $default, $length, $precision, $scale);
  }
}
