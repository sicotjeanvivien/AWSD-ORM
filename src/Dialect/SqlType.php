<?php

declare(strict_types=1);

namespace AWSD\ORM\Dialect;

/**
 * SQL types supported by the minimal PostgreSQL-first metadata layer.
 *
 * This enum represents a stable subset of column types commonly used in
 * PostgreSQL. It is intentionally conservative to keep the core mapping
 * predictable and portable. Vendor-specific extensions (MySQL/SQLite) can
 * be added later without breaking the public API.
 *
 * Notes:
 * - Temporal types: prefer TIMESTAMPTZ over TIMESTAMP unless timezone-less
 *   semantics are strictly required by the domain.
 * - JSON support: JSONB is the recommended default in PostgreSQL; default
 *   literals must be expressed as SQL EXPRESSION (e.g. '{}'::jsonb) at
 *   the DDL layer rather than raw JSON literals in metadata.
 * - Length/precision/scale rules are enforced at a higher layer (validator).
 */
enum SqlType: string
{
    /** 16-bit signed integer. */
    case SMALLINT    = 'smallint';

    /** 32-bit signed integer. */
    case INTEGER     = 'integer';

    /** 64-bit signed integer. */
    case BIGINT      = 'bigint';

    /**
     * Arbitrary precision numeric (DECIMAL). Requires precision (>=1) and
     * allows scale in [0..precision].
     */
    case NUMERIC     = 'numeric';

    /** Unbounded UTF-8 text (no length constraint). */
    case TEXT        = 'text';

    /**
     * Variable-length character string. Requires a positive length; use TEXT
     * when no upper bound is required.
     */
    case VARCHAR     = 'varchar';

    /** Binary data (byte array). */
    case BYTEA       = 'bytea';

    /** Boolean (true/false). */
    case BOOLEAN     = 'boolean';

    /** Universally unique identifier (128-bit), textual representation. */
    case UUID        = 'uuid';

    /** Calendar date without time-of-day or timezone. */
    case DATE        = 'date';

    /**
     * Timestamp without time zone. Use only if timezone handling is explicitly
     * managed at the application or connection level.
     */
    case TIMESTAMP   = 'timestamp';

    /**
     * Timestamp with time zone (recommended default for temporal columns in PG).
     * Stores an absolute point in time.
     */
    case TIMESTAMPTZ = 'timestamptz';

    /**
     * Binary JSON with indexing-friendly storage. Preferred over JSON in PG.
     * Defaults should be SQL expressions with explicit ::jsonb casts.
     */
    case JSONB       = 'jsonb';

    /**
     * Tell if the type is numeric (integers or arbitrary precision).
     *
     * @return bool True for SMALLINT, INTEGER, BIGINT, NUMERIC; false otherwise.
     */
    public function isNumeric(): bool
    {
        return match ($this) {
            self::SMALLINT,
            self::INTEGER,
            self::BIGINT,
            self::NUMERIC => true,
            default => false,
        };
    }

    /**
     * Tell if the type is textual (character data).
     *
     * @return bool True for TEXT or VARCHAR; false otherwise.
     */
    public function isTextual(): bool
    {
        return match ($this) {
            self::TEXT,
            self::VARCHAR => true,
            default => false,
        };
    }


    /**
     * Tell if the type is temporal (date/time family).
     *
     * @return bool True for DATE, TIMESTAMP, TIMESTAMPTZ; false otherwise.
     */
    public function isTemporal(): bool
    {
        return match ($this) {
            self::DATE,
            self::TIMESTAMP,
            self::TIMESTAMPTZ => true,
            default => false,
        };
    }


    /**
     * Tell if the type is the PostgreSQL JSONB type.
     *
     * @return bool True for JSONB; false otherwise.
     */
    public function isJsonb(): bool
    {
        return $this === self::JSONB;
    }

    /**
     * Tell if the type stores raw binary data.
     *
     * @return bool True for BYTEA; false otherwise.
     */
    public function isBinary(): bool
    {
        return $this === self::BYTEA;
    }

    /**
     * Tell if the type is boolean.
     *
     * @return bool True for BOOLEAN; false otherwise.
     */
    public function isBoolean(): bool
    {
        return $this === self::BOOLEAN;
    }

    /**
     * Tell if the type is UUID.
     *
     * @return bool True for UUID; false otherwise.
     */
    public function isUuid(): bool
    {
        return $this === self::UUID;
    }

    /**
     * Whether this type supports a length attribute in metadata.
     * (PostgreSQL-first: only VARCHAR supports length; TEXT does not.)
     *
     * @return bool True when a positive length is meaningful (VARCHAR); false otherwise.
     */
    public function supportsLength(): bool
    {
        return $this === self::VARCHAR;
    }

    /**
     * Whether this type supports precision/scale attributes in metadata.
     * (PostgreSQL-first: only NUMERIC supports precision/scale.)
     *
     * @return bool True when precision/scale are meaningful (NUMERIC); false otherwise.
     */
    public function supportsPrecisionScale(): bool
    {
        return $this === self::NUMERIC;
    }
}
