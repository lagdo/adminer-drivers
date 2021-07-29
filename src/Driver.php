<?php

namespace Lagdo\Adminer\Drivers;

$drivers = array();

/**
     * Add a driver
* @param string
* @param string
* @return null
*/
function add_driver($id, $name) {
    global $drivers;
    $drivers[$id] = $name;
}

class Driver implements DriverInterface
{
    /**
     * @var Adminer
     */
    protected $adminer;

    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $jush;

    /**
     * Create object for performing database operations
     * @param Min_DB
     */
    public function __construct($connection) {
        $this->connection = $connection;
    }

    /**
     * Select data from table
     * @param string
     * @param array result of $this->adminer->selectColumnsProcess()[0]
     * @param array result of $this->adminer->selectSearchProcess()
     * @param array result of $this->adminer->selectColumnsProcess()[1]
     * @param array result of $this->adminer->selectOrderProcess()
     * @param int result of $this->adminer->selectLimitProcess()
     * @param int index of page starting at zero
     * @param bool whether to print the query
     * @return Result
     */
    public function select($table, $select, $where, $group, $order = array(), $limit = 1, $page = 0, $print = false) {
        $is_group = (count($group) < count($select));
        $query = $this->adminer->selectQueryBuild($select, $where, $group, $order, $limit, $page);
        if (!$query) {
            $query = "SELECT" . $this->server->limit(
                ($_GET["page"] != "last" && $limit != "" && $group && $is_group && $this->jush == "sql" ? "SQL_CALC_FOUND_ROWS " : "") . implode(", ", $select) . "\nFROM " . $this->server->table($table),
                ($where ? "\nWHERE " . implode(" AND ", $where) : "") . ($group && $is_group ? "\nGROUP BY " . implode(", ", $group) : "") . ($order ? "\nORDER BY " . implode(", ", $order) : ""),
                ($limit != "" ? +$limit : null),
                ($page ? $limit * $page : 0),
                "\n"
            );
        }
        $start = microtime(true);
        $return = $this->connection->query($query);
        if ($print) {
            echo $this->adminer->selectQuery($query, $start, !$return);
        }
        return $return;
    }

    /**
     * Delete data from table
     * @param string
     * @param string " WHERE ..."
     * @param int 0 or 1
     * @return bool
     */
    public function delete($table, $queryWhere, $limit = 0) {
        $query = "FROM " . $this->server->table($table);
        return $this->server->queries("DELETE" . ($limit ? $this->server->limit1($table, $query, $queryWhere) : " $query$queryWhere"));
    }

    /**
     * Update data in table
     * @param string
     * @param array escaped columns in keys, quoted data in values
     * @param string " WHERE ..."
     * @param int 0 or 1
     * @param string
     * @return bool
     */
    public function update($table, $set, $queryWhere, $limit = 0, $separator = "\n") {
        $values = array();
        foreach ($set as $key => $val) {
            $values[] = "$key = $val";
        }
        $query = $this->server->table($table) . " SET$separator" . implode(",$separator", $values);
        return $this->server->queries("UPDATE" . ($limit ? $this->server->limit1($table, $query, $queryWhere, $separator) : " $query$queryWhere"));
    }

    /**
     * Insert data into table
     * @param string
     * @param array escaped columns in keys, quoted data in values
     * @return bool
     */
    public function insert($table, $set) {
        return $this->server->queries("INSERT INTO " . $this->server->table($table) . ($set
            ? " (" . implode(", ", array_keys($set)) . ")\nVALUES (" . implode(", ", $set) . ")"
            : " DEFAULT VALUES"
        ));
    }

    /**
     * Insert or update data in table
     * @param string
     * @param array
     * @param array of arrays with escaped columns in keys and quoted data in values
     * @return bool
     */
    /*abstract*/ function insertUpdate($table, $rows, $primary) {
        return false;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function begin() {
        return $this->server->queries("BEGIN");
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit() {
        return $this->server->queries("COMMIT");
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback() {
        return $this->server->queries("ROLLBACK");
    }

    /**
     * Return query with a timeout
     * @param string
     * @param int seconds
     * @return string or null if the driver doesn't support query timeouts
     */
    public function slowQuery($query, $timeout) {
    }

    /**
     * Convert column to be searchable
     * @param string escaped column name
     * @param array array("op" => , "val" => )
     * @param array
     * @return string
     */
    public function convertSearch($idf, $val, $field) {
        return $idf;
    }

    /**
     * Convert value returned by database to actual value
     * @param string
     * @param array
     * @return string
     */
    public function value($val, $field) {
        return (method_exists($this->connection, 'value')
            ? $this->connection->value($val, $field)
            : (is_resource($val) ? stream_get_contents($val) : $val)
        );
    }

    /**
     * Quote binary string
     * @param string
     * @return string
     */
    public function quoteBinary($s) {
        return $this->connection->quote($s);
    }

    /**
     * Get warnings about the last command
     * @return string HTML
     */
    public function warnings() {
        return '';
    }

    /**
     * Get help link for table
     * @param string
     * @return string relative URL or null
     */
    public function tableHelp($name) {
    }

}
