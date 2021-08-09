<?php

namespace Lagdo\Adminer\Drivers;

abstract class AbstractDriver implements DriverInterface
{
    /**
     * @var AdminerInterface
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
     * The constructor
     *
     * @param AdminerInterface
     * @param ServerInterface
     * @param ConnectionInterface
     */
    public function __construct(AdminerInterface $adminer, ServerInterface $server, ConnectionInterface $connection)
    {
        $this->adminer = $adminer;
        $this->server = $server;
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
     * @return Statement
     */
    public function select($table, $select, $where, $group, $order = [], $limit = 1, $page = 0)
    {
        $is_group = (count($group) < count($select));
        $query = $this->adminer->buildSelectQuery($select, $where, $group, $order, $limit, $page);
        if (!$query) {
            $query = "SELECT" . $this->server->limit(
                ($page != "last" && $limit != "" && $group && $is_group && $this->server->jush == "sql" ?
                "SQL_CALC_FOUND_ROWS " : "") . implode(", ", $select) . "\nFROM " .
                $this->server->table($table),
                ($where ? "\nWHERE " . implode(" AND ", $where) : "") . ($group && $is_group ?
                "\nGROUP BY " . implode(", ", $group) : "") . ($order ? "\nORDER BY " .
                implode(", ", $order) : ""),
                ($limit != "" ? +$limit : null),
                ($page ? $limit * $page : 0),
                "\n"
            );
        }
        $start = microtime(true);
        $return = $this->connection->query($query);
        return $return;
    }

    /**
     * Delete data from table
     * @param string
     * @param string " WHERE ..."
     * @param int 0 or 1
     * @return bool
     */
    public function delete($table, $queryWhere, $limit = 0)
    {
        $query = "FROM " . $this->server->table($table);
        return $this->adminer->queries("DELETE" .
            ($limit ? $this->server->limit1($table, $query, $queryWhere) : " $query$queryWhere"));
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
    public function update($table, $set, $queryWhere, $limit = 0, $separator = "\n")
    {
        $values = [];
        foreach ($set as $key => $val) {
            $values[] = "$key = $val";
        }
        $query = $this->server->table($table) . " SET$separator" . implode(",$separator", $values);
        return $this->adminer->queries("UPDATE" .
            ($limit ? $this->server->limit1($table, $query, $queryWhere, $separator) : " $query$queryWhere"));
    }

    /**
     * Insert data into table
     * @param string
     * @param array escaped columns in keys, quoted data in values
     * @return bool
     */
    public function insert($table, $set)
    {
        return $this->adminer->queries("INSERT INTO " . $this->server->table($table) . (
            $set
            ? " (" . implode(", ", array_keys($set)) . ")\nVALUES (" . implode(", ", $set) . ")"
            : " DEFAULT VALUES"
        ));
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function begin()
    {
        return $this->adminer->queries("BEGIN");
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit()
    {
        return $this->adminer->queries("COMMIT");
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback()
    {
        return $this->adminer->queries("ROLLBACK");
    }

    /**
     * Return query with a timeout
     * @param string
     * @param int seconds
     * @return string or null if the driver doesn't support query timeouts
     */
    public function slowQuery($query, $timeout)
    {
    }

    /**
     * Convert column to be searchable
     * @param string escaped column name
     * @param array array("op" => , "val" => )
     * @param array
     * @return string
     */
    public function convertSearch($idf, $val, $field)
    {
        return $idf;
    }

    /**
     * Convert value returned by database to actual value
     * @param string
     * @param array
     * @return string
     */
    public function value($val, $field)
    {
        return (is_resource($val) ? stream_get_contents($val) : $val);
    }

    /**
     * Quote binary string
     * @param string
     * @return string
     */
    public function quoteBinary($string)
    {
        return $this->connection->quote($string);
    }

    /**
     * Get warnings about the last command
     * @return string HTML
     */
    public function warnings()
    {
        return '';
    }

    /**
     * Get help link for table
     * @param string
     * @return string relative URL or null
     */
    public function tableHelp($name)
    {
        return '';
    }
}
