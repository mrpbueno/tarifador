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
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function int($value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
}