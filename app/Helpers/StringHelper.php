<?php

declare(strict_types=1);

namespace App\Helpers;

class StringHelper
{
    public static function snakeCaseToCamelCase(string $string)
    {
        return str_replace('_', '', ucwords($string, '_'));
    }
}
