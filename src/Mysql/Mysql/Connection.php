<?php

namespace Lagdo\Adminer\Drivers\Mysql\Mysql;

use Lagdo\Adminer\Drivers\AbstractConnection;

/**
 * MySQL driver to be used with the mysql PHP extension.
 */
class Connection extends AbstractConnection
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $_database = true;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'MySQL';
    }

     /**
     * @inheritDoc
     */
    public function connect($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        if (ini_bool("mysql.allow_local_infile")) {
            $this->error = lang('Disable %s or enable %s or %s extensions.', "'mysql.allow_local_infile'", "MySQLi", "PDO_MySQL");
            return false;
        }
        $this->client = @mysql_connect(
            ($server != "" ? $server : ini_get("mysql.default_host")),
            ("$server$username" != "" ? $username : ini_get("mysql.default_user")),
            ("$server$username$password" != "" ? $password : ini_get("mysql.default_password")),
            true,
            131072 // CLIENT_MULTI_RESULTS for CALL
        );
        if ($this->client) {
            $this->server_info = mysql_get_server_info($this->client);
        } else {
            $this->error = mysql_error();
        }
        return (bool) $this->client;
    }

    /**
     * Sets the client character set
     * @param string
     * @return bool
     */
    public function set_charset($charset) {
        if (function_exists('mysql_set_charset')) {
            if (mysql_set_charset($charset, $this->client)) {
                return true;
            }
            // the client library may not support utf8mb4
            mysql_set_charset('utf8', $this->client);
        }
        return $this->query("SET NAMES $charset");
    }

    /**
     * Quote string to use in SQL
     * @param string
     * @return string escaped string enclosed in '
     */
    public function quote($string) {
        return "'" . mysql_real_escape_string($string, $this->client) . "'";
    }

    /**
     * Select database
     * @param string
     * @return bool
     */
    public function select_db($database) {
        return mysql_select_db($database, $this->client);
    }

    /**
     * Send query
     * @param string
     * @param bool
     * @return mixed bool or Statement
     */
    public function query($query, $unbuffered = false) {
        // @ - mute mysql.trace_mode
        $result = @($unbuffered ? mysql_unbuffered_query($query, $this->client) : mysql_query($query, $this->client));
        $this->error = "";
        if (!$result) {
            $this->errno = mysql_errno($this->client);
            $this->error = mysql_error($this->client);
            return false;
        }
        if ($result === true) {
            $this->affected_rows = mysql_affected_rows($this->client);
            $this->info = mysql_info($this->client);
            return true;
        }
        return new Statement($result);
    }

    /**
     * Send query with more resultsets
     * @param string
     * @return bool
     */
    public function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    /**
     * Get current resultset
     * @return Statement
     */
    public function store_result() {
        return $this->_result;
    }

    /**
     * Fetch next resultset
     * @return bool
     */
    public function next_result() {
        // MySQL extension doesn't support multiple results
        return false;
    }

    /**
     * Get single field from result
     * @param string
     * @param int
     * @return string
     */
    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!$result || !$result->num_rows) {
            return false;
        }
        return mysql_result($result->_result, 0, $field);
    }
}
