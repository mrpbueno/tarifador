<?php


namespace FreePBX\modules\Tarifador\Utils;

/**
 * Class Sanitize
 * @package FreePBX\modules\Tarifador\Utils
 * @author Mauro <https://github.com/mrpbueno>
 */
class Sanitize
{
    public static function string($value)
    {
        return trim(strip_tags($value));
    }

    public static function int($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    public static function float($value)
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    public static function stringOutput($value)
    {
        return isset($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
    }

    public static function stringInput($value)
    {
        return isset($value) ? strip_tags(trim($value)) : '';
    }
}