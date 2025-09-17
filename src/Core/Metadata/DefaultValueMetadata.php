<?php

declare(strict_types=1);

namespace AWSD\ORM\Core;

use AWSD\ORM\Dialect\DefaultValueType;
use DateTimeInterface;

/**
 * Value Object representing a column default.
 * Immutable and explicitly typed.
 */
final readonly class DefaultValueMetadata
{
  /**
   * @param DefaultValueKind $type                        The kind of default.
   * @param int|float|bool|string|DateTimeInterface|null  $literal Scalar literal value (if kind=LITERAL). Unused otherwise.
   * @param string|null $expression                       SQL expression (if kind=EXPRESSION). Unused otherwise.
   */
  public function __construct(
    public DefaultValueType $type,
    public int|float|bool|string|DateTimeInterface|null $literal = null,
    public ?string $expression = null,
  ) {}

  /** @return self */
  public static function none(): self
  {
    return new self(DefaultValueType::NONE);
  }

  /**
   * @param int|float|bool|string|DateTimeInterface $value
   * @return self
   */
  public static function literal(int|float|bool|string|DateTimeInterface $value): self
  {
    return new self(DefaultValueType::LITERAL, $value, null);
  }

  /** @return self */
  public static function expression(string $sqlExpression): self
  {
    return new self(DefaultValueType::EXPRESSION, null, $sqlExpression);
  }

  /** True when a default is defined (literal or expression). */
  public function isDefined(): bool
  {
    return $this->type !== DefaultValueType::NONE;
  }

  /**
   * True if this default is explicitly a literal null.
   *
   * This is considered invalid in most cases because NULL defaults should
   * be expressed by making the column nullable (and leaving default NONE).
   *
   * @return bool
   */
  public function isLiteralNull(): bool
  {
    return $this->type === DefaultValueType::LITERAL && $this->literal === null;
  }

  /**
   * True if this default is a literal (non-null) value.
   *
   * @return bool
   */
  public function isLiteral(): bool
  {
    return $this->type === DefaultValueType::LITERAL && $this->literal !== null;
  }

  /**
   * True if this default is an SQL expression.
   *
   * @return bool
   */
  public function isExpression(): bool
  {
    return $this->type === DefaultValueType::EXPRESSION;
  }

  /**
   * True if no default is defined.
   *
   * @return bool
   */
  public function isNone(): bool
  {
    return $this->type === DefaultValueType::NONE;
  }
}
