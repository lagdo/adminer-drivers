<?php

namespace Lagdo\Adminer\Drivers\Mongo;

class Mongo {
    function table($idf) {
        return $idf;
    }

    function idf_escape($idf) {
        return $idf;
    }

    function table_status($name = "", $fast = false) {
        $return = array();
        foreach (tables_list() as $table => $type) {
            $return[$table] = array("Name" => $table);
            if ($name == $table) {
                return $return[$table];
            }
        }
        return $return;
    }

    function create_database($db, $collation) {
        return true;
    }

    function last_id() {
        global $connection;
        return $connection->last_id;
    }

    function error() {
        global $connection;
        return h($connection->error);
    }

    function collations() {
        return array();
    }

    function logged_user() {
        global $adminer;
        $credentials = $adminer->credentials();
        return $credentials[1];
    }

    function connect() {
        global $adminer;
        $connection = new Min_DB;
        list($server, $username, $password) = $adminer->credentials();
        $options = array();
        if ($username . $password != "") {
            $options["username"] = $username;
            $options["password"] = $password;
        }
        $db = $adminer->database();
        if ($db != "") {
            $options["db"] = $db;
        }
        if (($auth_source = getenv("MONGO_AUTH_SOURCE"))) {
            $options["authSource"] = $auth_source;
        }
        $connection->connect("mongodb://$server", $options);
        if ($connection->error) {
            return $connection->error;
        }
        return $connection;
    }

    function alter_indexes($table, $alter) {
        global $connection;
        foreach ($alter as $val) {
            list($type, $name, $set) = $val;
            if ($set == "DROP") {
                $return = $connection->_db->command(array("deleteIndexes" => $table, "index" => $name));
            } else {
                $columns = array();
                foreach ($set as $column) {
                    $column = preg_replace('~ DESC$~', '', $column, 1, $count);
                    $columns[$column] = ($count ? -1 : 1);
                }
                $return = $connection->_db->selectCollection($table)->ensureIndex($columns, array(
                    "unique" => ($type == "UNIQUE"),
                    "name" => $name,
                    //! "sparse"
                ));
            }
            if ($return['errmsg']) {
                $connection->error = $return['errmsg'];
                return false;
            }
        }
        return true;
    }

    function support($feature) {
        return preg_match("~database|indexes|descidx~", $feature);
    }

    function db_collation($db, $collations) {
    }

    function information_schema() {
    }

    function is_view($table_status) {
    }

    function convert_field($field) {
    }

    function unconvert_field($field, $return) {
        return $return;
    }

    function foreign_keys($table) {
        return array();
    }

    function fk_support($table_status) {
    }

    function engines() {
        return array();
    }

    function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        global $connection;
        if ($table == "") {
            $connection->_db->createCollection($name);
            return true;
        }
    }

    function drop_tables($tables) {
        global $connection;
        foreach ($tables as $table) {
            $response = $connection->_db->selectCollection($table)->drop();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    function truncate_tables($tables) {
        global $connection;
        foreach ($tables as $table) {
            $response = $connection->_db->selectCollection($table)->remove();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    function driver_config() {
        global $operators;
        return array(
            'possible_drivers' => array("mongo", "mongodb"),
            'jush' => "mongo",
            'operators' => $operators,
            'functions' => array(),
            'grouping' => array(),
            'edit_functions' => array(array("json")),
        );
    }
}
