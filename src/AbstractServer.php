<?php

namespace Lagdo\Adminer\Drivers;

use function Lagdo\Adminer\Drivers\h;
use function Lagdo\Adminer\Drivers\format_time;

abstract class AbstractServer implements ServerInterface
{
    /**
     * @var AdminerInterface
     */
    protected $adminer;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $database = '';

    /**
     * @var string
     */
    protected $schema = '';

    /**
     * The constructor
     *
     * @param AdminerInterface
     */
    public function __construct(AdminerInterface $adminer)
    {
        $this->adminer = $adminer;
    }

    /**
     * Create a connection to the server, based on the config and available packages
     *
     * @return void
     */
    abstract protected function createConnection();

    /**
     * @inheritDoc
     */
    public function selectDatabase(string $database, string $schema)
    {
        $this->database = $database;
        $this->schema = $schema;
        if($database !== '')
        {
            $this->connection->select_db($database);
            if($schema !== '')
            {
                $this->set_schema($schema);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getCurrentDatabase()
    {
        return $this->database;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSchema()
    {
        return $this->schema;
    }

    public function error() {
        return h($this->connection->error);
    }

    /**
     * @inheritDoc
     */
    public function idf_escape($idf)
    {
        return $idf;
    }

    /**
     * @inheritDoc
     */
    public function table($idf)
    {
        return $this->idf_escape($idf);
    }

    /**
     * @inheritDoc
     */
    public function view($name)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function engines()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function collations()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function db_collation($db, $collations) {
        return "";
    }

    /**
     * Get user defined types
     * @return array
     */
    public function types()
    {
        return [];
    }

    /**
     * Get existing schemas
     * @return array
     */
    public function schemas()
    {
        return [];
    }

    /**
     * Get current schema
     * @return string
     */
    public function get_schema()
    {
        return "";
    }

    /**
     * Set current schema
     * @param string
     * @param ConnectionInterface
     * @return bool
     */
    public function set_schema($schema, $connection2 = null)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function information_schema($db) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function is_view($table_status) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function foreign_keys($table) {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function fk_support($table_status) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function convert_field($field) {
    }

    /**
     * @inheritDoc
     */
    public function unconvert_field($field, $return) {
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function show_variables() {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function show_status() {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function explain($connection, $query) {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function use_sql($database) {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function last_id() {
        return $this->connection->last_id;
    }

    /**
     * @inheritDoc
     */
    public function found_rows($table_status, $where) {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function rename_database($name, $collation) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function auto_increment() {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function alter_indexes($table, $alter) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function drop_views($views) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function truncate_tables($tables) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function limit($query, $where, $limit, $offset = 0, $separator = " ") {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function limit1($table, $query, $where, $separator = "\n") {
        return $this->limit($query, $where, 1, 0, $separator);
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
            ($db != "" && $db != $this->database ? $this->idf_escape($db) . "." : "") .
            ($ns != "" && $ns != $this->schema ? $this->idf_escape($ns) . "." : "") .
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
        $server_info = $connection2->getServerInfo();
        if ($maria_db && preg_match('~([\d.]+)-MariaDB~', $server_info, $match)) {
            $server_info = $match[1];
            $version = $maria_db;
        }
        return (version_compare($server_info, $version) >= 0);
    }

    /**
     * Get connection charset
     *
     * @return string
     */
    public function charset()
    {
        // SHOW CHARSET would require an extra query
        return ($this->min_version("5.5.3", 0, $this->connection) ? "utf8mb4" : "utf8");
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
    public function get_rows($query, $connection2 = null)
    {
        $conn = (is_object($connection2) ? $connection2 : $this->connection);
        $return = [];
        $result = $conn->query($query);
        if (is_object($result)) { // can return true
            while ($row = $result->fetch_assoc()) {
                $return[] = $row;
            }
        }
        return $return;
    }
}
