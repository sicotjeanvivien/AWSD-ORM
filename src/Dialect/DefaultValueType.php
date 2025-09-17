<?php

declare(strict_types=1);

namespace AWSD\ORM\Dialect;

/**
 * Kind of default value attached to a column.
 *
 * Semantics (PostgreSQL-first, enforced by a higher-level validator):
 * - NONE:     No default will be emitted in DDL.
 * - LITERAL:  Scalar literal (int, float, bool, string, DateTime-like) to be bound
 *             as-is by the SQL layer. Forbidden for certain types:
 *             * Temporal (TIMESTAMP/TIMESTAMPTZ) → use EXPRESSION (e.g. now()).
 *             * JSONB → use EXPRESSION with explicit ::jsonb cast.
 * - EXPRESSION: Raw SQL expression evaluated by the database at insert time,
 *               e.g. now(), CURRENT_TIMESTAMP(3), '{}'::jsonb, jsonb_build_object().
 *
 * Notes:
 * - This enum only describes intent; dialect-level validation/casting is performed
 *   outside of the metadata (e.g., in a ColumnMetadataValidator and SQL mappers).
 * - Prefer EXPRESSION for non-deterministic or DB-generated values.
 */
enum DefaultValueType: string
{
/**
   * No default value is defined; the column must be provided at insert time
   * unless it is nullable or covered by other DB-side behaviors.
   */
  case NONE = 'none';

/**
   * Scalar literal to be inlined/bound by the SQL layer (e.g., 0, 1.5, true, 'foo').
   * Restrictions typically applied by validators:
   * - Not allowed for temporal columns (use EXPRESSION: now(), CURRENT_TIMESTAMP).
   * - Not allowed for JSONB (use EXPRESSION with ::jsonb).
   */
  case LITERAL = 'literal';

/**
   * Database expression evaluated at insert time (e.g., now(), CURRENT_TIMESTAMP(3), '{}'::jsonb).
   * Recommended for:
   * - Temporal defaults (now(), CURRENT_TIMESTAMP[(p)]).
   * - JSONB defaults with explicit cast or JSONB functions.
   */
  case EXPRESSION = 'expression';

  /**
   * Tell if the type is LITERAL.
   *
   * @return bool True for LITERAL; false otherwise.
   */
  public function isLiteral(): bool
  {
    return $this === self::LITERAL;
  }

  /**
   * Tell if the type is EXPRESSION.
   *
   * @return bool True for EXPRESSION; false otherwise.
   */
  public function isExpression(): bool
  {
    return $this === self::EXPRESSION;
  }
}
