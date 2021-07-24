<?php

namespace Lagdo\Adminer\Drivers\Elastic;

$drivers["elastic"] = "Elasticsearch (beta)";

if (isset($_GET["elastic"])) {
    define("DRIVER", "elastic");
}

class Elastic {
    function connect() {
        global $adminer;
        $connection = new Min_DB;
        list($server, $username, $password) = $adminer->credentials();
        if ($password != "" && $connection->connect($server, $username, "")) {
            return lang('Database does not support password.');
        }
        if ($connection->connect($server, $username, $password)) {
            return $connection;
        }
        return $connection->error;
    }

    function support($feature) {
        return preg_match("~database|table|columns~", $feature);
    }

    function logged_user() {
        global $adminer;
        $credentials = $adminer->credentials();
        return $credentials[1];
    }

    function get_databases() {
        global $connection;
        $return = $connection->rootQuery('_aliases');
        if ($return) {
            $return = array_keys($return);
            sort($return, SORT_STRING);
        }
        return $return;
    }

    function collations() {
        return array();
    }

    function db_collation($db, $collations) {
    }

    function engines() {
        return array();
    }

    function count_tables($databases) {
        global $connection;
        $return = array();
        $result = $connection->query('_stats');
        if ($result && $result['indices']) {
            $indices = $result['indices'];
            foreach ($indices as $indice => $stats) {
                $indexing = $stats['total']['indexing'];
                $return[$indice] = $indexing['index_total'];
            }
        }
        return $return;
    }

    function tables_list() {
        global $connection;

        if (min_version(6)) {
            return array('_doc' => 'table');
        }

        $return = $connection->query('_mapping');
        if ($return) {
            $return = array_fill_keys(array_keys($return[$connection->_db]["mappings"]), 'table');
        }
        return $return;
    }

    function table_status($name = "", $fast = false) {
        global $connection;
        $search = $connection->query("_search", array(
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

    function error() {
        global $connection;
        return h($connection->error);
    }

    function information_schema() {
    }

    function is_view($table_status) {
    }

    function indexes($table, $connection2 = null) {
        return array(
            array("type" => "PRIMARY", "columns" => array("_id")),
        );
    }

    function fields($table) {
        global $connection;

        $mappings = array();
        if (min_version(6)) {
            $result = $connection->query("_mapping");
            if ($result) {
                $mappings = $result[$connection->_db]['mappings']['properties'];
            }
        } else {
            $result = $connection->query("$table/_mapping");
            if ($result) {
                $mappings = $result[$table]['properties'];
                if (!$mappings) {
                    $mappings = $result[$connection->_db]['mappings'][$table]['properties'];
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

    function foreign_keys($table) {
        return array();
    }

    function table($idf) {
        return $idf;
    }

    function idf_escape($idf) {
        return $idf;
    }

    function convert_field($field) {
    }

    function unconvert_field($field, $return) {
        return $return;
    }

    function fk_support($table_status) {
    }

    function found_rows($table_status, $where) {
        return null;
    }

    /** Create index
    * @param string
    * @return mixed
    */
    function create_database($db) {
        global $connection;
        return $connection->rootQuery(urlencode($db), null, 'PUT');
    }

    /** Remove index
    * @param array
    * @return mixed
    */
    function drop_databases($databases) {
        global $connection;
        return $connection->rootQuery(urlencode(implode(',', $databases)), array(), 'DELETE');
    }

    /** Alter type
    * @param array
    * @return mixed
    */
    function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        global $connection;
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
        return $connection->query("_mapping/{$name}", $properties, 'PUT');
    }

    /** Drop types
    * @param array
    * @return bool
    */
    function drop_tables($tables) {
        global $connection;
        $return = true;
        foreach ($tables as $table) { //! convert to bulk api
            $return = $return && $connection->query(urlencode($table), array(), 'DELETE');
        }
        return $return;
    }

    function last_id() {
        global $connection;
        return $connection->last_id;
    }

    function driver_config() {
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
}
