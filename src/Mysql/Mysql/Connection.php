<?php

namespace Lagdo\Adminer\Drivers\Mysql\Mysql;

use Lagdo\Adminer\Drivers\ConnectionInterface;

/**
 * MySQL driver to be used with the mysql PHP extension.
 */
class Connection implements ConnectionInterface
{
    var
        $extension = "MySQL", ///< @var string extension name
        $server_info, ///< @var string server version
        $affected_rows, ///< @var int number of affected rows
        $errno, ///< @var int last error code
        $error, ///< @var string last error message
        $_link, $_result ///< @access private
    ;

    /**
     * Connect to server
     * @param string
     * @param string
     * @param string
     * @return bool
     */
    public function connect($server, $username, $password) {
        if (ini_bool("mysql.allow_local_infile")) {
            $this->error = lang('Disable %s or enable %s or %s extensions.', "'mysql.allow_local_infile'", "MySQLi", "PDO_MySQL");
            return false;
        }
        $this->_link = @mysql_connect(
            ($server != "" ? $server : ini_get("mysql.default_host")),
            ("$server$username" != "" ? $username : ini_get("mysql.default_user")),
            ("$server$username$password" != "" ? $password : ini_get("mysql.default_password")),
            true,
            131072 // CLIENT_MULTI_RESULTS for CALL
        );
        if ($this->_link) {
            $this->server_info = mysql_get_server_info($this->_link);
        } else {
            $this->error = mysql_error();
        }
        return (bool) $this->_link;
    }

    /**
     * Sets the client character set
     * @param string
     * @return bool
     */
    public function set_charset($charset) {
        if (function_exists('mysql_set_charset')) {
            if (mysql_set_charset($charset, $this->_link)) {
                return true;
            }
            // the client library may not support utf8mb4
            mysql_set_charset('utf8', $this->_link);
        }
        return $this->query("SET NAMES $charset");
    }

    /**
     * Quote string to use in SQL
     * @param string
     * @return string escaped string enclosed in '
     */
    public function quote($string) {
        return "'" . mysql_real_escape_string($string, $this->_link) . "'";
    }

    /**
     * Select database
     * @param string
     * @return bool
     */
    public function select_db($database) {
        return mysql_select_db($database, $this->_link);
    }

    /**
     * Send query
     * @param string
     * @param bool
     * @return mixed bool or Statement
     */
    public function query($query, $unbuffered = false) {
        // @ - mute mysql.trace_mode
        $result = @($unbuffered ? mysql_unbuffered_query($query, $this->_link) : mysql_query($query, $this->_link));
        $this->error = "";
        if (!$result) {
            $this->errno = mysql_errno($this->_link);
            $this->error = mysql_error($this->_link);
            return false;
        }
        if ($result === true) {
            $this->affected_rows = mysql_affected_rows($this->_link);
            $this->info = mysql_info($this->_link);
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
