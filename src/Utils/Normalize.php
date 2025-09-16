<?php

declare(strict_types=1);

namespace AWSD\ORM\Utils;

final class Normalize
{
  public static function string(string $string): string
  {
    return trim($string);
  }

  /**
   * Convert a string to snake_case ASCII.
   *
   * Rules:
   * - Transliterate to ASCII first (é→e, œ→oe, ß→ss, …).
   * - Replace any non-alphanumeric separator with a single underscore.
   * - Split camelCase boundaries and ACRONYM+Word boundaries.
   * - Optionally separate letter↔digit boundaries.
   * - Collapse multiple underscores and trim leading/trailing underscores.
   *
   * Implementation notes:
   * - Uses {@see self::toAscii()} which prefers intl\Transliterator if available,
   *   and falls back to iconv otherwise.
   * - The function is locale-independent and expects UTF-8 input.
   *
   * @param string $value           Input string (UTF-8 expected).
   * @param bool   $separateDigits  When true, inserts '_' between letters and digits (e.g. "json2XML" → "json_2_xml").
   *
   * @return string Snake_case ASCII string (lowercase).
   *
   * @example Normalize::snakeAscii('CrèmeBrûlée')          // 'creme_brulee'
   * @example Normalize::snakeAscii('UserProfile')          // 'user_profile'
   * @example Normalize::snakeAscii('HTTPServerID')         // 'http_server_id'
   * @example Normalize::snakeAscii('JSON2XMLParser')       // 'json_2_xml_parser'
   * @example Normalize::snakeAscii('StraßeNr5')            // 'strasse_nr_5'
   */
  public static function toSnakeCaseAscii(string $value, bool $separateDigits = true): string
  {
    $value = \trim($value);
    $value = self::toAscii($value); // remove diacritics and map special letters

    // 1) Replace any non-alphanumeric run with '_'
    $value = \preg_replace('/[^A-Za-z0-9]+/', '_', $value);

    // 2) camelCase boundaries: xA -> x_A
    $value = \preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value);
    //    ACRONYM+Word boundaries: ABCDef -> ABC_Def
    $value = \preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value);

    // 3) Optional letter↔digit boundaries
    if ($separateDigits) {
      $value = \preg_replace('/([A-Za-z])([0-9])/', '$1_$2', $value);
      $value = \preg_replace('/([0-9])([A-Za-z])/', '$1_$2', $value);
    }

    // 4) Cleanup: collapse multiple underscores and trim edges
    $value = \preg_replace('/_{2,}/', '_', $value);
    $value = \trim($value, '_');

    // 5) Lowercase (ASCII)
    return \strtolower($value);
  }

  /**
   * Transliterate a UTF-8 string to ASCII.
   *
   * Prefers the intl\Transliterator (php-intl extension) with the rule:
   *   "Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove"
   * Falls back to iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ...) when intl is not available.
   *
   * Typical mappings:
   * - "é" → "e", "à" → "a", "ñ" → "n"
   * - "œ" → "oe", "Æ" → "AE", "ß" → "ss"
   *
   * @param string $value Input UTF-8 string.
   *
   * @return string ASCII-only string. If transliteration fails, returns the original input.
   */
  private static function toAscii(string $value): string
  {
    // Prefer intl Transliterator if available
    if (\class_exists(\Transliterator::class)) {
      $tr = \Transliterator::create('Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove');
      if ($tr instanceof \Transliterator) {
        $result = $tr->transliterate($value);
        if ($result !== null) {
          return $result;
        }
      }
    }

    // Fallback to iconv
    $converted = @\iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return $converted !== false ? $converted : $value;
  }
}
