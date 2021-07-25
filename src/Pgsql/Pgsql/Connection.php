<?php

namespace Lagdo\Adminer\Drivers\Pgsql\Pgsql;

use Lagdo\Adminer\Drivers\ConnectionInterface;

/**
 * PostgreSQL driver to be used with the pgsql PHP extension.
 */
class Connection implements ConnectionInterface
{
    var $extension = "PgSQL", $_link, $_result, $_string, $_database = true, $server_info, $affected_rows, $error, $timeout;

    public function _error($errno, $error) {
        if (ini_bool("html_errors")) {
            $error = html_entity_decode(strip_tags($error));
        }
        $error = preg_replace('~^[^:]*: ~', '', $error);
        $this->error = $error;
    }

    public function connect($server, $username, $password) {
        global $adminer;
        $db = $adminer->database();
        set_error_handler(array($this, '_error'));
        $this->_string = "host='" . str_replace(":", "' port='", addcslashes($server, "'\\")) . "' user='" . addcslashes($username, "'\\") . "' password='" . addcslashes($password, "'\\") . "'";
        $this->_link = @pg_connect("$this->_string dbname='" . ($db != "" ? addcslashes($db, "'\\") : "postgres") . "'", PGSQL_CONNECT_FORCE_NEW);
        if (!$this->_link && $db != "") {
            // try to connect directly with database for performance
            $this->_database = false;
            $this->_link = @pg_connect("$this->_string dbname='postgres'", PGSQL_CONNECT_FORCE_NEW);
        }
        restore_error_handler();
        if ($this->_link) {
            $version = pg_version($this->_link);
            $this->server_info = $version["server"];
            pg_set_client_encoding($this->_link, "UTF8");
        }
        return (bool) $this->_link;
    }

    public function quote($string) {
        return "'" . pg_escape_string($this->_link, $string) . "'";
    }

    public function value($val, $field) {
        return ($field["type"] == "bytea" && $val !== null ? pg_unescape_bytea($val) : $val);
    }

    public function quoteBinary($string) {
        return "'" . pg_escape_bytea($this->_link, $string) . "'";
    }

    public function select_db($database) {
        global $adminer;
        if ($database == $adminer->database()) {
            return $this->_database;
        }
        $return = @pg_connect("$this->_string dbname='" . addcslashes($database, "'\\") . "'", PGSQL_CONNECT_FORCE_NEW);
        if ($return) {
            $this->_link = $return;
        }
        return $return;
    }

    public function close() {
        $this->_link = @pg_connect("$this->_string dbname='postgres'");
    }

    public function query($query, $unbuffered = false) {
        $result = @pg_query($this->_link, $query);
        $this->error = "";
        if (!$result) {
            $this->error = pg_last_error($this->_link);
            $return = false;
        } elseif (!pg_num_fields($result)) {
            $this->affected_rows = pg_affected_rows($result);
            $return = true;
        } else {
            $return = new Result($result);
        }
        if ($this->timeout) {
            $this->timeout = 0;
            $this->query("RESET statement_timeout");
        }
        return $return;
    }

    public function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    public function store_result() {
        return $this->_result;
    }

    public function next_result() {
        // PgSQL extension doesn't support multiple results
        return false;
    }

    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!$result || !$result->num_rows) {
            return false;
        }
        return pg_fetch_result($result->_result, 0, $field);
    }

    public function warnings() {
        return h(pg_last_notice($this->_link)); // second parameter is available since PHP 7.1.0
    }
}
