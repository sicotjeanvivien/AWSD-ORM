<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata;

use AWSD\ORM\Core\Metadata\Validator\ColumnMetadataValidator;
use AWSD\ORM\Dialect\SqlType;
use AWSD\ORM\Utils\Normalize;
use AWSD\ORM\Core\DefaultValueMetadata;

/**
 * Class ColumnMetadata
 *
 * Immutable value object describing a logical database column (PostgreSQL-first).
 * This class carries metadata only. It does NOT generate SQL and does NOT enforce
 * dialect-specific rules at runtime. Validation should be handled by a dedicated
 * validator component at a higher level (e.g., ColumnMetadataValidator).
 *
 * Intended usage (V1 rules, to be validated externally):
 * - VARCHAR requires a positive length (> 0). Other types must not define length.
 * - NUMERIC requires a precision >= 1; scale must satisfy 0 <= scale <= precision.
 * - JSONB forbids length/precision/scale and forbids LITERAL defaults
 *   (only EXPRESSION defaults like `'{}'::jsonb` are acceptable).
 * - TIMESTAMP/TIMESTAMPTZ forbid LITERAL defaults; allow EXPRESSION defaults such as
 *   `now()` or `CURRENT_TIMESTAMP[(p)]`.
 * - Primary/Unique constraints are NOT modeled here; use dedicated constraint metadata objects.
 *
 * Notes:
 * - Names are logical/unquoted; quoting/escaping is the responsibility of the SQL layer.
 * - This class is dialect-aware only by documentation; do not assume runtime checks here.
 */
final readonly class ColumnMetadata
{
  /**
   * Logical, unquoted column name.
   * Quoting and case-sensitivity rules are applied later by the SQL layer.
   *
   * @var string
   */
  private readonly string $name;

  /**
   * Declared SQL type of the column (PostgreSQL-first enumeration).
   * Extend the enum to support additional dialect-specific types.
   *
   * @var SqlType
   */
  private readonly SqlType $type;

  /**
   * Length for character-varying types.
   * Must be strictly positive for VARCHAR; MUST be null for all other types.
   *
   * @var int|null
   */
  private readonly ?int $length;

  /**
   * Total number of significant digits for NUMERIC types.
   * Must be >= 1 for NUMERIC; MUST be null for non-NUMERIC types.
   *
   * @var int|null
   */
  private readonly ?int $precision;

  /**
   * Number of digits to the right of the decimal point for NUMERIC types.
   * Must satisfy 0 <= scale <= precision for NUMERIC; MUST be null for non-NUMERIC types.
   * Keep null when "unspecified" (your validator/normalizer may translate it to 0).
   *
   * @var int|null
   */
  private readonly ?int $scale;

  /**
   * Whether the column accepts NULL values.
   * Primary key and other constraint semantics are not modeled here.
   *
   * @var bool
   */
  private readonly bool $nullable;

  /**
   * Column default descriptor (NONE, LITERAL, or EXPRESSION).
   * V1 recommendations to enforce in a validator:
   * - JSONB: only EXPRESSION defaults (e.g., `'{}'::jsonb`), never LITERAL.
   * - TIMESTAMP/TIMESTAMPTZ: EXPRESSION defaults such as `now()`, `CURRENT_TIMESTAMP[(p)]`.
   *
   * @var DefaultValueMetadata
   */
  private readonly DefaultValueMetadata $default;

  /**
   * Construct a new immutable column metadata instance.
   *
   * This constructor delegates basic shape validation to ColumnMetadataValidator.
   *
   * @param string               $name
   * @param SqlType              $type
   * @param bool                 $nullable
   * @param DefaultValueMetadata $default
   * @param int|null             $length
   * @param int|null             $precision
   * @param int|null             $scale
   *
   * @throws InvalidArgumentException When length/precision/scale or defaults are inconsistent with $type.
   */
  public function __construct(
    string $name,
    SqlType $type,
    bool $nullable,
    DefaultValueMetadata $default,
    ?int $length = null,
    ?int $precision = null,
    ?int $scale = null
  ) {
    $this->name      = Normalize::toSnakeCaseAscii($name);
    $this->type      = $type;
    $this->nullable  = $nullable;
    $this->default   = $default;
    $this->length    = $length;
    $this->precision = $precision;
    $this->scale     = $scale;

    $this->validate();
  }


  /**
   * Return the logical (unquoted) column name.
   *
   * @return string
   */
  public function name(): string
  {
    return $this->name;
  }

  /**
   * Return the column SQL type.
   *
   * @return SqlType
   */
  public function type(): SqlType
  {
    return $this->type;
  }

  /**
   * Return the VARCHAR length if defined; null for non-VARCHAR types.
   *
   * @return int|null
   */
  public function length(): ?int
  {
    return $this->length;
  }

  /**
   * Return the NUMERIC precision if defined; null for non-NUMERIC types.
   *
   * @return int|null
   */
  public function precision(): ?int
  {
    return $this->precision;
  }

  /**
   * Return the NUMERIC scale if defined; null for non-NUMERIC types.
   * If your pipeline normalizes an unspecified scale to 0, that transformation
   * should happen outside of this class (e.g., in a validator/normalizer).
   *
   * @return int|null
   */
  public function scale(): ?int
  {
    return $this->scale;
  }

  /**
   * Indicate whether NULL values are allowed for this column.
   *
   * @return bool
   */
  public function nullable(): bool
  {
    return $this->nullable;
  }

  /**
   * Return the default value descriptor.
   * See class-level notes for recommended semantics per type (JSONB, temporal, etc.).
   *
   * @return DefaultValueMetadata
   */
  public function default(): DefaultValueMetadata
  {
    return $this->default;
  }

  /**
   * Delegate type-specific validation to ColumnMetadataValidator.
   *
   * @throws InvalidArgumentException
   */
  private function validate(): void
  {
    ColumnMetadataValidator::nameValidator($this->name);
    ColumnMetadataValidator::reservedWordValidator($this->name);

    match ($this->type->supportsLength()) {
      true  => ColumnMetadataValidator::varcharValidator($this->length),
      false => ColumnMetadataValidator::notVarcharValidator($this->length),
    };

    match ($this->type->supportsPrecisionScale()) {
      true  => ColumnMetadataValidator::numericValidator($this->precision, $this->scale),
      false => ColumnMetadataValidator::notNumericValidator($this->precision, $this->scale),
    };

    ColumnMetadataValidator::defaultCompatibilityByType(
      $this->type,
      $this->default,
      $this->length,
      $this->precision,
      $this->scale
    );
  }
}
