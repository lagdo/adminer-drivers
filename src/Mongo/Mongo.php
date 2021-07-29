<?php

namespace Lagdo\Adminer\Drivers\Mongo;

class Mongo
{
    /**
      * Get a connection to the server, based on the config and available packages
      */
    protected function createConnection()
    {
        if(class_exists('MongoDB'))
        {
            return new Mongo\Connection();
        }
        if(class_exists('MongoDB\Driver\Manager'))
        {
            return new MongoDb\Connection();
        }
        return null;
    }

    /**
      * @inheritDoc
      */
    public function connect()
    {
        global $adminer;
        $connection = $this->createConnection();
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

    /**
      * @inheritDoc
      */
    public function idf_escape($idf)
    {
        return $idf;
    }

    public function table($idf) {
        return $idf;
    }

    public function limit($query, $where, $limit, $offset = 0, $separator = " ") {
        return "";
    }

    public function limit1($table, $query, $where, $separator = "\n") {
        return "";
    }

    public function table_status($name = "", $fast = false) {
        $return = array();
        foreach (tables_list() as $table => $type) {
            $return[$table] = array("Name" => $table);
            if ($name == $table) {
                return $return[$table];
            }
        }
        return $return;
    }

    public function create_database($db, $collation) {
        return true;
    }

    public function rename_database($name, $collation) {
        return false;
    }

    public function auto_increment() {
        return "";
    }

    public function last_id() {
        global $connection;
        return $connection->last_id;
    }

    public function error() {
        global $connection;
        return h($connection->error);
    }

    public function collations() {
        return array();
    }

    public function logged_user() {
        global $adminer;
        $credentials = $adminer->credentials();
        return $credentials[1];
    }

    public function alter_indexes($table, $alter) {
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

    public function drop_views($views) {
        return false;
    }

    public function support($feature) {
        return preg_match("~database|indexes|descidx~", $feature);
    }

    public function db_collation($db, $collations) {
    }

    public function information_schema($db) {
        return null;
    }

    public function is_view($table_status) {
        return false;
    }

    public function convert_field($field) {
    }

    public function unconvert_field($field, $return) {
        return $return;
    }

    public function foreign_keys($table) {
        return array();
    }

    public function fk_support($table_status) {
        return false;
    }

    public function view($name) {
        return array();
    }

    public function engines() {
        return array();
    }

    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        global $connection;
        if ($table == "") {
            $connection->_db->createCollection($name);
            return true;
        }
    }

    public function drop_tables($tables) {
        global $connection;
        foreach ($tables as $table) {
            $response = $connection->_db->selectCollection($table)->drop();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    public function truncate_tables($tables) {
        global $connection;
        foreach ($tables as $table) {
            $response = $connection->_db->selectCollection($table)->remove();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    public function driver_config() {
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
