<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata\Validator;

use AWSD\ORM\Dialect\SqlType;
use AWSD\ORM\Core\DefaultValueMetadata; // Assure-toi que ce namespace est le bon dans tout le projet
use InvalidArgumentException;

/**
 * DefaultValueMetadataValidator
 *
 * V1 — PostgreSQL-first.
 * Validates the compatibility of a column default with its SQL type and (optionally)
 * with column shape attributes (length / precision / scale).
 *
 * Scope:
 * - No normalization is performed here (e.g., scale=null is not rewritten to 0).
 * - JSONB defaults: only SQL expressions (e.g., '{}'::jsonb), never LITERAL.
 * - Temporal defaults (TIMESTAMP / TIMESTAMPTZ): only whitelisted expressions (now(),
 *   CURRENT_TIMESTAMP[(p)], etc.). For DATE, CURRENT_DATE is allowed.
 * - NUMERIC literal defaults must respect precision/scale if provided.
 * - VARCHAR literal defaults must not exceed the declared length if provided.
 * - BOOLEAN literal defaults must be true/false.
 * - UUID literal defaults must match canonical UUID regex.
 * - BYTEA defaults are not supported in V1 (neither LITERAL nor EXPRESSION).
 *
 * Note:
 * - Scientific notation for NUMERIC literal defaults: if a float literal is provided
 *   and the string format does not match the regex, V1 conservatively skips digit-count
 *   checks (accepts the value). This can be tightened in a future version.
 */
final class DefaultValueMetadataValidator
{
  /**
   * Entry point for default validation per type.
   *
   * @param SqlType               $type       Column SQL type (PG-first).
   * @param DefaultValueMetadata  $default    Default value descriptor (kind + payload).
   * @param int|null              $length     Column length (only meaningful for VARCHAR).
   * @param int|null              $precision  Column precision (only meaningful for NUMERIC).
   * @param int|null              $scale      Column scale (only meaningful for NUMERIC).
   *
   * @throws InvalidArgumentException If the default is incompatible with the given type or shape.
   */
  public static function validate(
    SqlType $type,
    DefaultValueMetadata $default,
    ?int $length,
    ?int $precision,
    ?int $scale
  ): void {
    // Disallow DEFAULT NULL as a literal (design choice): rely on nullable=true instead.
    if ($default->isLiteralNull()) {
      throw new InvalidArgumentException('NULL literal defaults are not supported; rely on nullable=true instead.');
    }

    match (true) {
      $type->isJsonb()    => self::jsonbDefaultValidator($default),
      $type->isTemporal() => self::temporalDefaultValidator($default, $type),
      $type->isNumeric()  => self::numericDefaultValidator($default, $precision, $scale),
      $type->isTextual()  => self::textualDefaultValidator($default, $length),
      $type->isBoolean()  => self::booleanDefaultValidator($default),
      $type->isUuid()     => self::uuidDefaultValidator($default),
      $type->isBinary()   => self::binaryDefaultValidator($default),
      default             => self::validateGeneric($default),
    };
  }

  /**
   * Validate JSONB default semantics (PostgreSQL).
   * - LITERAL defaults are forbidden.
   * - EXPRESSION defaults must be non-empty (e.g., '{}'::jsonb).
   *
   * @param DefaultValueMetadata $default
   * @throws InvalidArgumentException
   */
  public static function jsonbDefaultValidator(DefaultValueMetadata $default): void
  {
    if ($default->type->isLiteral()) {
      throw new InvalidArgumentException(
        "JSONB columns do not accept LITERAL defaults. Use an EXPRESSION with ::jsonb (e.g., '{}'::jsonb)."
      );
    }

    if ($default->type->isExpression()) {
      $expr = trim((string) $default->expression);
      if ($expr === '') {
        throw new InvalidArgumentException('JSONB default EXPRESSION cannot be empty.');
      }
      // Option (V1 strict): enforce ::jsonb cast or jsonb_* functions here.
    }
  }

  /**
   * Validate temporal default semantics.
   * - TIMESTAMP/TIMESTAMPTZ: only whitelisted expressions (now(), CURRENT_TIMESTAMP[(p)], ...).
   * - DATE: CURRENT_DATE is allowed.
   * - LITERAL defaults are forbidden for all temporal types.
   *
   * @param DefaultValueMetadata $default
   * @param SqlType              $type
   * @throws InvalidArgumentException
   */
  public static function temporalDefaultValidator(DefaultValueMetadata $default, SqlType $type): void
  {
    // DATE: allow CURRENT_DATE
    if ($type === SqlType::DATE && $default->type->isExpression()) {
      $expr = strtolower(trim((string) $default->expression));
      if ($expr === 'current_date') {
        return;
      }
    }

    if ($default->type->isLiteral()) {
      throw new InvalidArgumentException(
        $type->value . ' does not accept LITERAL defaults. Use an EXPRESSION like CURRENT_TIMESTAMP or now().'
      );
    }

    if ($default->type->isExpression()) {
      $expr = strtolower(trim((string) $default->expression));
      if ($expr === '') {
        throw new InvalidArgumentException('Temporal default EXPRESSION cannot be empty.');
      }

      $allowed = [
        'now()',
        'current_timestamp',
        'statement_timestamp()',
        'transaction_timestamp()',
        'clock_timestamp()',
      ];
      $isCurrentTimestampWithPrecision = (bool) preg_match('/^current_timestamp\(\d+\)$/', $expr);

      if (!in_array($expr, $allowed, true) && !$isCurrentTimestampWithPrecision) {
        throw new InvalidArgumentException("Unsupported temporal default expression for PostgreSQL: '{$expr}'.");
      }
    }
  }

  /**
   * Validate NUMERIC literal defaults against precision/scale (if provided).
   * - Literal must be int or float.
   * - If precision is provided, checks total digits and fractional digits (scale).
   * - Floats in scientific notation are accepted in V1 (digit-count check relaxed).
   *
   * @param DefaultValueMetadata $default
   * @param int|null             $precision
   * @param int|null             $scale
   * @throws InvalidArgumentException
   */
  public static function numericDefaultValidator(DefaultValueMetadata $default, ?int $precision, ?int $scale): void
  {
    if ($default->type->isLiteral()) {
      if (!is_int($default->literal) && !is_float($default->literal)) {
        throw new InvalidArgumentException('NUMERIC literal default must be an int or float.');
      }
      if ($precision !== null) {
        $s   = $scale ?? 0;
        // Accept scientific notation if literal is a float (digit-count check relaxed)
        $val = is_float($default->literal) ? sprintf('%.15g', $default->literal) : (string) $default->literal;
        if (!preg_match('/^-?\d+(?:\.(\d+))?$/', $val, $m)) {
          if (!is_float($default->literal)) {
            throw new InvalidArgumentException('NUMERIC literal default is not a valid number string.');
          }
        } else {
          $intDigits  = strlen(ltrim(explode('.', $val)[0], '-'));
          $fracDigits = isset($m[1]) ? strlen($m[1]) : 0;
          if ($intDigits + $fracDigits > $precision || $fracDigits > $s) {
            throw new InvalidArgumentException('NUMERIC literal default exceeds precision/scale constraints.');
          }
        }
      }
    }
  }

  /**
   * Validate textual literal defaults against VARCHAR length (if provided).
   *
   * @param DefaultValueMetadata $default
   * @param int|null             $length
   * @throws InvalidArgumentException
   */
  public static function textualDefaultValidator(DefaultValueMetadata $default, ?int $length): void
  {
    if ($default->type->isLiteral() && $length !== null) {
      if (mb_strlen((string) $default->literal, 'UTF-8') > $length) {
        throw new InvalidArgumentException('VARCHAR literal default exceeds defined length.');
      }
    }
  }

  /**
   * Validate BOOLEAN literal defaults (must be true/false).
   *
   * @param DefaultValueMetadata $default
   * @throws InvalidArgumentException
   */
  public static function booleanDefaultValidator(DefaultValueMetadata $default): void
  {
    if ($default->type->isLiteral() && !is_bool($default->literal)) {
      throw new InvalidArgumentException('BOOLEAN literal default must be true or false.');
    }
  }

  /**
   * Validate UUID literal defaults (canonical format).
   *
   * @param DefaultValueMetadata $default
   * @throws InvalidArgumentException
   */
  public static function uuidDefaultValidator(DefaultValueMetadata $default): void
  {
    if ($default->type->isLiteral()) {
      $val = (string) $default->literal;
      if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $val)) {
        throw new InvalidArgumentException('UUID literal default is not a valid UUID.');
      }
    }
    // V1: EXPRESSION (e.g., gen_random_uuid()) allowed without strict whitelist.
  }

  /**
   * Validate BYTEA defaults (forbidden in V1, both LITERAL and EXPRESSION).
   *
   * @param DefaultValueMetadata $default
   * @throws InvalidArgumentException
   */
  public static function binaryDefaultValidator(DefaultValueMetadata $default): void
  {
    if ($default->type->isLiteral() || $default->type->isExpression()) {
      throw new InvalidArgumentException('BYTEA defaults are not supported in V1.');
    }
  }

  /**
   * Generic validation for types not requiring special handling in V1.
   * Currently a no-op (SMALLINT / INTEGER / BIGINT, etc.).
   *
   * @param DefaultValueMetadata $default
   * @throws InvalidArgumentException
   */
  private static function validateGeneric(DefaultValueMetadata $default): void
  {
    // Intentionally left blank in V1.
    // You may later restrict EXPRESSION defaults here if desired.
  }
}
