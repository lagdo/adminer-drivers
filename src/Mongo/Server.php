<?php

namespace Lagdo\Adminer\Drivers\Mongo;

use Lagdo\Adminer\Drivers\AbstractServer;

class Server extends AbstractServer
{
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
        $options = [];
        if ($username . $password != "") {
            $options["username"] = $username;
            $options["password"] = $password;
        }
        $db = $this->getCurrentDatabase();
        if ($db != "") {
            $options["db"] = $db;
        }
        if (($auth_source = getenv("MONGO_AUTH_SOURCE"))) {
            $options["authSource"] = $auth_source;
        }
        $this->connection->open("mongodb://$server", $options);
        if ($this->connection->error) {
            return $this->connection->error;
        }

        return $this->connection;
    }

    public function table_status($name = "", $fast = false) {
        $return = [];
        foreach ($this->tables_list() as $table => $type) {
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

    public function logged_user() {
        $credentials = $this->adminer->credentials();
        return $credentials[1];
    }

    public function alter_indexes($table, $alter) {
        foreach ($alter as $val) {
            list($type, $name, $set) = $val;
            if ($set == "DROP") {
                $return = $this->connection->_db->command(array("deleteIndexes" => $table, "index" => $name));
            } else {
                $columns = [];
                foreach ($set as $column) {
                    $column = preg_replace('~ DESC$~', '', $column, 1, $count);
                    $columns[$column] = ($count ? -1 : 1);
                }
                $return = $this->connection->_db->selectCollection($table)->ensureIndex($columns, array(
                    "unique" => ($type == "UNIQUE"),
                    "name" => $name,
                    //! "sparse"
                ));
            }
            if ($return['errmsg']) {
                $this->connection->error = $return['errmsg'];
                return false;
            }
        }
        return true;
    }

    public function support($feature) {
        return preg_match("~database|indexes|descidx~", $feature);
    }

    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        if ($table == "") {
            $this->connection->_db->createCollection($name);
            return true;
        }
    }

    public function drop_tables($tables) {
        foreach ($tables as $table) {
            $response = $this->connection->_db->selectCollection($table)->drop();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    public function truncate_tables($tables) {
        foreach ($tables as $table) {
            $response = $this->connection->_db->selectCollection($table)->remove();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    public function driver_config() {
        return array(
            'possible_drivers' => array("mongo", "mongodb"),
            'jush' => "mongo",
            'operators' => $this->adminer->operators(),
            'functions' => [],
            'grouping' => [],
            'edit_functions' => array(array("json")),
        );
    }
}
