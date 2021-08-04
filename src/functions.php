<?php

namespace Lagdo\Adminer\Drivers;

use function substr;
use function str_replace;
use function Lagdo\Adminer\Drivers\lang;

/**
 * Escape for HTML
 * @param string
 * @return string
 */
function h($string)
{
    return str_replace("\0", "&#0;", htmlspecialchars($string, ENT_QUOTES, 'utf-8'));
}

/**
 * Check whether the string is in UTF-8
 * @param string
 * @return bool
 */
function is_utf8($val)
{
    // don't print control chars except \t\r\n
    return (preg_match('~~u', $val) && !preg_match('~[\0-\x8\xB\xC\xE-\x1F]~', $val));
}

/**
 * Unescape database identifier
 * @param string $idf
 * @return string
 */
function idf_unescape($idf)
{
    $last = substr($idf, -1);
    return str_replace($last . $last, $last, substr($idf, 1, -1));
}

/**
 * Get regular expression to match numeric types
 * @return string
 */
function number_type()
{
    return '((?<!o)int(?!er)|numeric|real|float|double|decimal|money)'; // not point, not interval
}

/**
 * Format elapsed time
 * @param float output of microtime(true)
 * @return string HTML code
 */
function format_time($start)
{
    return lang('%.3f s', max(0, microtime(true) - $start));
}

/**
 * Format decimal number
 * @param int
 * @return string
 */
function format_number($val)
{
    return strtr(number_format($val, 0, ".", lang(',')), preg_split('~~u', lang('0123456789'), -1, PREG_SPLIT_NO_EMPTY));
}

/**
 * Find unique identifier of a row
 * @param array
 * @param array result of indexes()
 * @return array or null if there is no unique identifier
 */
function unique_array($row, $indexes)
{
    foreach ($indexes as $index) {
        if (preg_match("~PRIMARY|UNIQUE~", $index["type"])) {
            $return = [];
            foreach ($index["columns"] as $key) {
                if (!isset($row[$key])) { // NULL is ambiguous
                    continue 2;
                }
                $return[$key] = $row[$key];
            }
            return $return;
        }
    }
}
