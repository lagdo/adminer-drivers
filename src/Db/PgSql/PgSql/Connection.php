<?php

namespace Lagdo\Adminer\Drivers\Db\PgSql\PgSql;

use Lagdo\Adminer\Drivers\Db\Connection as AbstractConnection;

/**
 * PostgreSQL driver to be used with the pgsql PHP extension.
 */
class Connection extends AbstractConnection
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    public $_string;

    /**
     * Undocumented variable
     *
     * @var string
     */
    public $_database = true;

    /**
     * Undocumented variable
     *
     * @var int
     */
    public $timeout;

    public function _error($errno, $error)
    {
        if ($this->util->ini_bool("html_errors")) {
            $error = html_entity_decode(strip_tags($error));
        }
        $error = preg_replace('~^[^:]*: ~', '', $error);
        $this->error = $error;
    }

    /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        $db = $this->server->current_db();
        set_error_handler(array($this, '_error'));
        $this->_string = "host='" . str_replace(":", "' port='", addcslashes($server, "'\\")) .
            "' user='" . addcslashes($username, "'\\") . "' password='" . addcslashes($password, "'\\") . "'";
        $this->client = @pg_connect("$this->_string dbname='" .
            ($db != "" ? addcslashes($db, "'\\") : "postgres") . "'", PGSQL_CONNECT_FORCE_NEW);
        if (!$this->client && $db != "") {
            // try to connect directly with database for performance
            $this->_database = false;
            $this->client = @pg_connect("$this->_string dbname='postgres'", PGSQL_CONNECT_FORCE_NEW);
        }
        restore_error_handler();
        if ($this->client) {
            $version = pg_version($this->client);
            $this->server_info = $version["server"];
            pg_set_client_encoding($this->client, "UTF8");
        }
        return (bool) $this->client;
    }

    /**
     * @inheritDoc
     */
    public function quote($string)
    {
        return "'" . pg_escape_string($this->client, $string) . "'";
    }

    /**
     * @inheritDoc
     */
    public function value($val, $field)
    {
        $type = $field["type"] ?? '';
        return ($type == "bytea" && $val !== null ? pg_unescape_bytea($val) : $val);
    }

    /**
     * @inheritDoc
     */
    public function quoteBinary($string)
    {
        return "'" . pg_escape_bytea($this->client, $string) . "'";
    }

    /**
     * @inheritDoc
     */
    public function select_db($database)
    {
        if ($database == $this->server->current_db()) {
            return $this->_database;
        }
        $return = @pg_connect("$this->_string dbname='" . addcslashes($database, "'\\") . "'", PGSQL_CONNECT_FORCE_NEW);
        if ($return) {
            $this->client = $return;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        $this->client = @pg_connect("$this->_string dbname='postgres'");
    }

    /**
     * @inheritDoc
     */
    public function query($query, $unbuffered = false)
    {
        $result = @pg_query($this->client, $query);
        $this->error = "";
        if (!$result) {
            $this->error = pg_last_error($this->client);
            $return = false;
        } elseif (!pg_num_fields($result)) {
            $this->affected_rows = pg_affected_rows($result);
            $return = true;
        } else {
            $return = new Statement($result);
        }
        if ($this->timeout) {
            $this->timeout = 0;
            $this->query("RESET statement_timeout");
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function multi_query($query)
    {
        return $this->_result = $this->query($query);
    }

    /**
     * @inheritDoc
     */
    public function store_result($result = null)
    {
        return $this->_result;
    }

    /**
     * @inheritDoc
     */
    public function next_result()
    {
        // PgSQL extension doesn't support multiple results
        return false;
    }

    /**
     * @inheritDoc
     */
    public function result($query, $field = 0)
    {
        $result = $this->query($query);
        if (!$result || !$result->num_rows) {
            return false;
        }
        return pg_fetch_result($result->_result, 0, $field);
    }

    /**
     * @inheritDoc
     */
    public function warnings()
    {
        // second parameter is available since PHP 7.1.0
        return $this->util->h(pg_last_notice($this->client));
    }
}
