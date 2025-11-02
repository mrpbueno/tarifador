<?php

declare(strict_types=1);

namespace FreePBX\modules\Tarifador\Utils;

/**
 * Class Sanitize
 * @package FreePBX\modules\Tarifador\Utils
 * @author Mauro <https://github.com/mrpbueno>
 */
final class Sanitize
{
    private function __construct()
    {
        // Utility class
    }

    public static function string(?string $value): string
    {
        return trim(strip_tags($value ?? ''));
    }

    public static function int(mixed $value): int|false
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    public static function float(mixed $value): float|false
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    public static function stringOutput(mixed $value, string $default = ''): string
    {
        if (!isset($value) || !is_scalar($value)) {
            return $default;
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function stringInput(mixed $value): string
    {
        if (!isset($value) || !is_scalar($value)) {
            return '';
        }
        return strip_tags(trim((string) $value));
    }
}