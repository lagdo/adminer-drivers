<?php

namespace Lagdo\Adminer\Drivers;

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
     * Execute and remember query
     * @param string or null to return remembered queries, end with ';' to use DELIMITER
     * @return Statement or array($queries, $time) if $query = null
     */
    public function queries($query)
    {
        static $queries = array();
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
}
