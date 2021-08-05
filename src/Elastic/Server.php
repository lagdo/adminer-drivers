<?php

namespace Lagdo\Adminer\Drivers\Elastic;

use Lagdo\Adminer\Drivers\AbstractServer;

use function Lagdo\Adminer\Drivers\lang;

class Server extends AbstractServer
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "Elasticsearch (beta)";
    }

    /**
     * @inheritDoc
     */
    protected function createConnection()
    {
        if(($this->connection))
        {
            // Do not create if it already exists
            return;
        }

        if(function_exists('json_decode') && ini_bool('allow_url_fopen'))
        {
            $this->connection = new Connection($this->adminer, $this, 'JSON');
        }
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        $this->createConnection();
        if (!$this->connection) {
            return null;
        }

        list($server, $username, $password) = $this->adminer->credentials();
        if ($password != "" &&
            $this->connection->open($server, ['username' => $username, 'password' => ""]))
        {
            return lang('Database does not support password.');
        }
        if (!$this->connection->open($server, \compact('username', 'password'))) {
            return $this->connection->error;
        }

        $this->driver = new Driver($this->adminer, $this, $this->connection);
        return $this->connection;
    }

    public function support($feature) {
        return preg_match("~database|table|columns~", $feature);
    }

    public function logged_user() {
        $credentials = $this->adminer->credentials();
        return $credentials[1];
    }

    public function get_databases($flush) {
        $return = $this->connection->rootQuery('_aliases');
        if ($return) {
            $return = array_keys($return);
            sort($return, SORT_STRING);
        }
        return $return;
    }

    public function count_tables($databases) {
        $return = [];
        $result = $this->connection->query('_stats');
        if ($result && $result['indices']) {
            $indices = $result['indices'];
            foreach ($indices as $indice => $stats) {
                $indexing = $stats['total']['indexing'];
                $return[$indice] = $indexing['index_total'];
            }
        }
        return $return;
    }

    public function tables_list() {
        if ($this->min_version(6)) {
            return array('_doc' => 'table');
        }

        $return = $this->connection->query('_mapping');
        if ($return) {
            $return = array_fill_keys(array_keys($return[$this->connection->_db]["mappings"]), 'table');
        }
        return $return;
    }

    public function table_status($name = "", $fast = false) {
        $search = $this->connection->query("_search", array(
            "size" => 0,
            "aggregations" => array(
                "count_by_type" => array(
                    "terms" => array(
                        "field" => "_type"
                    )
                )
            )
        ), "POST");
        $return = [];
        if ($search) {
            $tables = $search["aggregations"]["count_by_type"]["buckets"];
            foreach ($tables as $table) {
                $return[$table["key"]] = array(
                    "Name" => $table["key"],
                    "Engine" => "table",
                    "Rows" => $table["doc_count"],
                );
                if ($name != "" && $name == $table["key"]) {
                    return $return[$name];
                }
            }
        }
        return $return;
    }

    public function indexes($table, $connection2 = null) {
        return array(
            array("type" => "PRIMARY", "columns" => array("_id")),
        );
    }

    public function fields($table) {
        $mappings = [];
        if ($this->min_version(6)) {
            $result = $this->connection->query("_mapping");
            if ($result) {
                $mappings = $result[$this->connection->_db]['mappings']['properties'];
            }
        } else {
            $result = $this->connection->query("$table/_mapping");
            if ($result) {
                $mappings = $result[$table]['properties'];
                if (!$mappings) {
                    $mappings = $result[$this->connection->_db]['mappings'][$table]['properties'];
                }
            }
        }

        $return = [];
        if ($mappings) {
            foreach ($mappings as $name => $field) {
                $return[$name] = array(
                    "field" => $name,
                    "full_type" => $field["type"],
                    "type" => $field["type"],
                    "privileges" => array("insert" => 1, "select" => 1, "update" => 1),
                );
                if ($field["properties"]) { // only leaf fields can be edited
                    unset($return[$name]["privileges"]["insert"]);
                    unset($return[$name]["privileges"]["update"]);
                }
            }
        }
        return $return;
    }

    /**
     * Create index
     * @param string
     * @return mixed
     */
    public function create_database($db, $collation) {
        return $this->connection->rootQuery(urlencode($db), null, 'PUT');
    }

    /**
     * Remove index
     * @param array
     * @return mixed
     */
    public function drop_databases($databases) {
        return $this->connection->rootQuery(urlencode(implode(',', $databases)), [], 'DELETE');
    }

    /**
     * Alter type
     * @param array
     * @return mixed
     */
    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        $properties = [];
        foreach ($fields as $f) {
            $field_name = trim($f[1][0]);
            $field_type = trim($f[1][1] ? $f[1][1] : "text");
            $properties[$field_name] = array(
                'type' => $field_type
            );
        }
        if (!empty($properties)) {
            $properties = array('properties' => $properties);
        }
        return $this->connection->query("_mapping/{$name}", $properties, 'PUT');
    }

    /**
     * Drop types
     * @param array
     * @return bool
     */
    public function drop_tables($tables) {
        $return = true;
        foreach ($tables as $table) { //! convert to bulk api
            $return = $return && $this->connection->query(urlencode($table), [], 'DELETE');
        }
        return $return;
    }

    public function driver_config() {
        $types = [];
        $structured_types = [];
        foreach (array(
            lang('Numbers') => array("long" => 3, "integer" => 5, "short" => 8, "byte" => 10,
                "double" => 20, "float" => 66, "half_float" => 12, "scaled_float" => 21),
            lang('Date and time') => array("date" => 10),
            lang('Strings') => array("string" => 65535, "text" => 65535),
            lang('Binary') => array("binary" => 255),
        ) as $key => $val) {
            $types += $val;
            $structured_types[$key] = array_keys($val);
        }
        return array(
            'possible_drivers' => array("json + allow_url_fopen"),
            'jush' => "elastic",
            'operators' => array("=", "query"),
            'functions' => [],
            'grouping' => [],
            'edit_functions' => array(array("json")),
            'types' => $types,
            'structured_types' => $structured_types,
        );
    }
}
