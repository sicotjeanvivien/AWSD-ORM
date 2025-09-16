<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata\Validator;

/**
 * Validator for PostgreSQL *unquoted* identifiers used as table and schema names.
 *
 * This class enforces the following constraints (after upstream normalization):
 *  - Non-empty string
 *  - Maximum length of 63 characters (PostgreSQL default identifier limit)
 *  - Pattern match for unquoted identifiers in lowercase snake_case: /^[a-z_][a-z0-9_]*$/
 *
 * Notes:
 *  - This validator does **not** perform normalization (trim/snake/lowercase); do that beforehand.
 *  - This validator does **not** perform SQL quoting; quoting must be handled by the SQL renderer.
 *  - On violation, an \InvalidArgumentException is thrown with a precise, stable message suitable for TDD.
 */
final class TableMetadataValidator
{
    /** PostgreSQL default maximum length for identifiers. */
    private const MAX_IDENTIFIER_LENGTH = 63;

    /** Regex for unquoted PostgreSQL identifiers in lowercase snake_case. */
    private const IDENTIFIER_REGEX = '/^[a-z_][a-z0-9_]*$/';

    /**
     * Validate a (normalized) table name.
     *
     * Expected input:
     *  - Already trimmed and lowercased
     *  - Already converted to snake_case if needed
     *
     * @param string $name Table name to validate (unquoted identifier).
     *
     * @throws \InvalidArgumentException If the name is empty, exceeds 63 characters,
     *                                   or does not match the required pattern.
     */
    public static function validateTableName(string $name): void
    {
        self::assertIdentifier($name, 'Table name', 'Invalid unquoted table identifier');
    }

    /**
     * Validate a (normalized) schema name.
     *
     * Expected input:
     *  - Already trimmed and lowercased
     *  - Already converted to snake_case if needed
     *
     * @param string $name Schema name to validate (unquoted identifier).
     *
     * @throws \InvalidArgumentException If the name is empty, exceeds 63 characters,
     *                                   or does not match the required pattern.
     */
    public static function validateSchemaName(string $name): void
    {
        self::assertIdentifier($name, 'Schema name', 'Invalid unquoted schema identifier');
    }

    /**
     * Assert common identifier constraints for unquoted PostgreSQL names.
     *
     * @param string $value        The candidate identifier (expected normalized).
     * @param string $emptyLabel   Human-friendly label used in the "empty" error message
     *                             (e.g., "Table name", "Schema name").
     * @param string $invalidLabel Human-friendly label used in the "invalid pattern" message
     *                             (e.g., "Invalid unquoted table identifier").
     *
     * @throws \InvalidArgumentException If the identifier is empty, too long, or invalid.
     */
    private static function assertIdentifier(string $value, string $emptyLabel, string $invalidLabel): void
    {
        $value = \trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException($emptyLabel . ' must be a non-empty string');
        }

        if (\strlen($value) > self::MAX_IDENTIFIER_LENGTH) {
            throw new \InvalidArgumentException(
                'Identifier exceeds PostgreSQL limit of 63 characters: ' . self::preview($value)
            );
        }

        if (!\preg_match(self::IDENTIFIER_REGEX, $value)) {
            throw new \InvalidArgumentException($invalidLabel . ': ' . self::preview($value));
        }
    }

    /**
     * Produce a short, safe preview of a potentially long identifier for error messages.
     *
     * @param string $s   Original string.
     * @param int    $max Maximum number of characters to keep before appending an ellipsis.
     *
     * @return string Preview string (unchanged if within the limit; otherwise truncated with '…').
     */
    private static function preview(string $s, int $max = 40): string
    {
        return \strlen($s) <= $max ? $s : (\substr($s, 0, $max) . '…');
    }
}
