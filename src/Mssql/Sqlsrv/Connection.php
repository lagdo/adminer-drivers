<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Sqlsrv;

use Lagdo\Adminer\Drivers\ConnectionInterface;

class Connection implements ConnectionInterface
{
    var $extension = "sqlsrv", $_link, $_result, $server_info, $affected_rows, $errno, $error;

    public function _get_error() {
        $this->error = "";
        foreach (sqlsrv_errors() as $error) {
            $this->errno = $error["code"];
            $this->error .= "$error[message]\n";
        }
        $this->error = rtrim($this->error);
    }

    public function connect($server, $username, $password) {
        $db = $this->adminer->database();
        $connection_info = array("UID" => $username, "PWD" => $password, "CharacterSet" => "UTF-8");
        if ($db != "") {
            $connection_info["Database"] = $db;
        }
        $this->_link = @sqlsrv_connect(preg_replace('~:~', ',', $server), $connection_info);
        if ($this->_link) {
            $info = sqlsrv_server_info($this->_link);
            $this->server_info = $info['SQLServerVersion'];
        } else {
            $this->_get_error();
        }
        return (bool) $this->_link;
    }

    public function quote($string) {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    public function select_db($database) {
        return $this->query("USE " . $this->server->idf_escape($database));
    }

    public function query($query, $unbuffered = false) {
        $result = sqlsrv_query($this->_link, $query); //! , array(), ($unbuffered ? array() : array("Scrollable" => "keyset"))
        $this->error = "";
        if (!$result) {
            $this->_get_error();
            return false;
        }
        return $this->store_result($result);
    }

    public function multi_query($query) {
        $this->_result = sqlsrv_query($this->_link, $query);
        $this->error = "";
        if (!$this->_result) {
            $this->_get_error();
            return false;
        }
        return true;
    }

    public function store_result($result = null) {
        if (!$result) {
            $result = $this->_result;
        }
        if (!$result) {
            return false;
        }
        if (sqlsrv_field_metadata($result)) {
            return new Statement($result);
        }
        $this->affected_rows = sqlsrv_rows_affected($result);
        return true;
    }

    public function next_result() {
        return $this->_result ? sqlsrv_next_result($this->_result) : null;
    }

    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!is_object($result)) {
            return false;
        }
        $row = $result->fetch_row();
        return $row[$field];
    }
}
