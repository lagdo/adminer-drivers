<?php

namespace Lagdo\Adminer\Drivers\Elastic;

use Lagdo\Adminer\Drivers\Server;

class Elastic extends Server
{
    /**
     * @inheritDoc
     */
    public function getDriver()
    {
        return "elastic";
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "Elasticsearch (beta)";
    }

    /**
     * Get a connection to the server, based on the config and available packages
     */
    protected function createConnection()
    {
        if(function_exists('json_decode') && ini_bool('allow_url_fopen'))
        {
            return new Connection();
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        $connection = $this->createConnection();
        list($server, $username, $password) = $this->adminer->credentials();
        if ($password != "" && $this->connection->connect($server, $username, "")) {
            return lang('Database does not support password.');
        }
        if ($this->connection->connect($server, $username, $password)) {
            return $connection;
        }
        return $this->connection->error;
    }

    /**
     * @inheritDoc
     */
    public function idf_escape($idf)
    {
        return $idf;
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

    public function limit($query, $where, $limit, $offset = 0, $separator = " ") {
        return "";
    }

    public function collations() {
        return array();
    }

    public function db_collation($db, $collations) {
    }

    public function engines() {
        return array();
    }

    public function count_tables($databases) {
        $return = array();
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
        if (min_version(6)) {
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
        $return = array();
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

    public function error() {
        return h($this->connection->error);
    }

    public function information_schema($db) {
        return null;
    }

    public function is_view($table_status) {
        return false;
    }

    public function indexes($table, $connection2 = null) {
        return array(
            array("type" => "PRIMARY", "columns" => array("_id")),
        );
    }

    public function fields($table) {
        $mappings = array();
        if (min_version(6)) {
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

        $return = array();
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

    public function foreign_keys($table) {
        return array();
    }

    public function table($idf) {
        return $idf;
    }

    public function convert_field($field) {
    }

    public function unconvert_field($field, $return) {
        return $return;
    }

    public function fk_support($table_status) {
        return false;
    }

    public function view($name) {
        return array();
    }

    public function found_rows($table_status, $where) {
        return null;
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
        return $this->connection->rootQuery(urlencode(implode(',', $databases)), array(), 'DELETE');
    }

    public function rename_database($name, $collation) {
        return false;
    }

    public function auto_increment() {
        return "";
    }

    public function alter_indexes($table, $alter) {
        return false;
    }

    public function drop_views($views) {
        return false;
    }

    public function truncate_tables($tables) {
        return false;
    }

    /**
     * Alter type
     * @param array
     * @return mixed
     */
    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        $properties = array();
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
            $return = $return && $this->connection->query(urlencode($table), array(), 'DELETE');
        }
        return $return;
    }

    public function last_id() {
        return $this->connection->last_id;
    }

    public function driver_config() {
        $types = array();
        $structured_types = array();
        foreach (array(
            lang('Numbers') => array("long" => 3, "integer" => 5, "short" => 8, "byte" => 10, "double" => 20, "float" => 66, "half_float" => 12, "scaled_float" => 21),
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
            'functions' => array(),
            'grouping' => array(),
            'edit_functions' => array(array("json")),
            'types' => $types,
            'structured_types' => $structured_types,
        );
    }

    public function explain($connection, $query) {
        return null;
    }

    public function schemas() {
        return array();
    }

    public function get_schema() {
        return "";
    }

    public function set_schema($schema, $connection2 = null) {
        return true;
    }

    public function show_variables() {
        return array();
    }

    public function show_status() {
        return array();
    }
}
