<?php

namespace App\Utils;

class Validate
{
    public static function notNull($value, $length = null): bool
    {
        if ($value == '0') {
            return false;
        }
        if ($length != null and strlen($value) > $length) {
            return false;
        }
        if (is_array($value)) {
            if (sizeof($value) > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            if (($value != '') && (@strtolower($value) != 'null') && (@strlen(@trim($value)) > 0)) {
                return true;
            } else {
                return false;
            }
        }
    }
}