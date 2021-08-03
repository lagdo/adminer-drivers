<?php

namespace Lagdo\Adminer\Drivers;

use function Lagdo\Adminer\Drivers\format_time;

abstract class AbstractServer implements ServerInterface
{
    /**
     * @var Adminer
     */
    protected $adminer;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * Get a connection to the server, based on the config and available packages
     */
    abstract protected function createConnection();

    /**
     * Get user defined types
     * @return array
     */
    public function types() {
        return [];
    }

    /**
     * Get existing schemas
     * @return array
     */
    public function schemas() {
        return [];
    }

    /**
     * Get current schema
     * @return string
     */
    public function get_schema() {
        return "";
    }

    /**
     * Set current schema
     * @param string
     * @param ConnectionInterface
     * @return bool
     */
    public function set_schema($schema, $connection2 = null) {
        return true;
    }

    /**
     * Get default value clause
     * @param array
     * @return string
     */
    public function default_value($field)
    {
        $default = $field["default"];
        return ($default === null ? "" : " DEFAULT " .
            (preg_match('~char|binary|text|enum|set~', $field["type"]) ||
            preg_match('~^(?![a-z])~i', $default) ? $this->q($default) : $default));
    }

    /**
     * Get status of a single table and fall back to name on error
     * @param string
     * @param bool
     * @return array
     */
    public function table_status1($table, $fast = false)
    {
        $return = $this->table_status($table, $fast);
        return ($return ? $return : array("Name" => $table));
    }

    /**
     * Format foreign key to use in SQL query
     * @param array ("db" => string, "ns" => string, "table" => string, "source" => array, "target" => array, "on_delete" => one of $on_actions, "on_update" => one of $on_actions)
     * @return string
     */
    function format_foreign_key($foreign_key)
    {
        global $on_actions;
        $db = $foreign_key["db"];
        $ns = $foreign_key["ns"];
        return " FOREIGN KEY (" . implode(", ", array_map(function($idf) {
                return $this->idf_escape($idf);
            }, $foreign_key["source"])) . ") REFERENCES " .
            ($db != "" && $db != $_GET["db"] ? $this->idf_escape($db) . "." : "") .
            ($ns != "" && $ns != $_GET["ns"] ? $this->idf_escape($ns) . "." : "") .
            $this->table($foreign_key["table"]) . " (" . implode(", ", array_map(function($idf) {
                return $this->idf_escape($idf);
            }, $foreign_key["target"])) . ")" . //! reuse $name - check in older MySQL versions
            (preg_match("~^($on_actions)\$~", $foreign_key["on_delete"]) ? " ON DELETE $foreign_key[on_delete]" : "") .
            (preg_match("~^($on_actions)\$~", $foreign_key["on_update"]) ? " ON UPDATE $foreign_key[on_update]" : "")
        ;
    }

    /**
     * Execute and remember query
     * @param string or null to return remembered queries, end with ';' to use DELIMITER
     * @return Statement or array($queries, $time) if $query = null
     */
    public function queries($query)
    {
        static $queries = [];
        static $start;
        if (!$start) {
            $start = microtime(true);
        }
        if ($query === null) {
            // return executed queries
            return array(implode("\n", $queries), format_time($start));
        }
        $queries[] = (preg_match('~;$~', $query) ? "DELIMITER ;;\n$query;\nDELIMITER " : $query) . ";";
        return $this->connection->query($query);
    }

    /**
     * Apply command to all array items
     * @param string
     * @param array
     * @param callback
     * @return bool
     */
    public function apply_queries($query, $tables, $escape = 'table')
    {
        foreach ($tables as $table) {
            if (!$this->queries("$query " . $escape($table))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get list of values from database
     * @param string
     * @param mixed
     * @return array
     */
    public function get_vals($query, $column = 0)
    {
        $return = [];
        $result = $this->connection->query($query);
        if (is_object($result)) {
            while ($row = $result->fetch_row()) {
                $return[] = $row[$column];
            }
        }
        return $return;
    }

    /**
     * Check if connection has at least the given version
     * @param string required version
     * @param string required MariaDB version
     * @param ConnectionInterface defaults to $this->connection
     * @return bool
     */
    public function min_version($version, $maria_db = "", $connection2 = null)
    {
        if (!$connection2) {
            $connection2 = $this->connection;
        }
        $server_info = $connection2->server_info;
        if ($maria_db && preg_match('~([\d.]+)-MariaDB~', $server_info, $match)) {
            $server_info = $match[1];
            $version = $maria_db;
        }
        return (version_compare($server_info, $version) >= 0);
    }

    /**
     * Shortcut for $this->connection->quote($string)
     * @param string
     * @return string
     */
    public function q($string)
    {
        return $this->connection->quote($string);
    }

    /**
     * Get keys from first column and values from second
     * @param string
     * @param ConnectionInterface
     * @param bool
     * @return array
     */
    public function get_key_vals($query, $connection2 = null, $set_keys = true)
    {
        if (!is_object($connection2)) {
            $connection2 = $this->connection;
        }
        $return = [];
        $result = $connection2->query($query);
        if (is_object($result)) {
            while ($row = $result->fetch_row()) {
                if ($set_keys) {
                    $return[$row[0]] = $row[1];
                } else {
                    $return[] = $row[0];
                }
            }
        }
        return $return;
    }

    /**
     * Get all rows of result
     * @param string
     * @param ConnectionInterface
     * @param string
     * @return array of associative arrays
     */
    public function get_rows($query, $connection2 = null, $error = "<p class='error'>")
    {
        $conn = (is_object($connection2) ? $connection2 : $this->connection);
        $return = [];
        $result = $conn->query($query);
        if (is_object($result)) { // can return true
            while ($row = $result->fetch_assoc()) {
                $return[] = $row;
            }
        } elseif (!$result && !is_object($connection2) && $error && defined("PAGE_HEADER")) {
            echo $error . error() . "\n";
        }
        return $return;
    }
}