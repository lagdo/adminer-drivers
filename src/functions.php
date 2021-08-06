<?php

namespace Lagdo\Adminer\Drivers;

use function substr;
use function str_replace;

/**
 * Get an instance of a database server class
 *
 * @param AdminerInterface $adminer
 * @param string $server
 *
 * @return Lagdo\Adminer\Drivers\ServerInterface
 */
function getDbServer(AdminerInterface $adminer, string $server)
{
    switch($server)
    {
    case "mysql":
        return new Lagdo\Adminer\Drivers\MySql\Server($adminer);
    case "pgsql":
        return new Lagdo\Adminer\Drivers\PgSql\Server($adminer);
    case "oracle":
        return new Lagdo\Adminer\Drivers\Oracle\Server($adminer);
    case "mssql":
        return new Lagdo\Adminer\Drivers\MsSql\Server($adminer);
    case "mongo":
        if(class_exists('MongoDB'))
        {
            return new Lagdo\Adminer\Drivers\Mongo\Mongo\Server($adminer);
        }
        if(class_exists('MongoDB\Driver\Manager'))
        {
            return new Lagdo\Adminer\Drivers\Mongo\MongoDb\Server($adminer);
        }
    case "elastic":
        return new Lagdo\Adminer\Drivers\Elastic\Server($adminer);
    case "sqlite":
    case "sqlite2":
        return new Lagdo\Adminer\Drivers\Sqlite\Server($adminer, $server);
    }
    return null;
}

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
 * Escape string to use inside ''
 * @param string
 * @return string
 */
function escape_string($val)
{
	return substr(q($val), 1, -1);
}

/**
 * Remove non-digits from a string
 * @param string
 * @return string
 */
function number($val)
{
	return preg_replace('~[^0-9]+~', '', $val);
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
