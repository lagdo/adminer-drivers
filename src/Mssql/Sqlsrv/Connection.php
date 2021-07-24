<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Sqlsrv;

class Connection {
    var $extension = "sqlsrv", $_link, $_result, $server_info, $affected_rows, $errno, $error;

    function _get_error() {
        $this->error = "";
        foreach (sqlsrv_errors() as $error) {
            $this->errno = $error["code"];
            $this->error .= "$error[message]\n";
        }
        $this->error = rtrim($this->error);
    }

    function connect($server, $username, $password) {
        global $adminer;
        $db = $adminer->database();
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

    function quote($string) {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    function select_db($database) {
        return $this->query("USE " . idf_escape($database));
    }

    function query($query, $unbuffered = false) {
        $result = sqlsrv_query($this->_link, $query); //! , array(), ($unbuffered ? array() : array("Scrollable" => "keyset"))
        $this->error = "";
        if (!$result) {
            $this->_get_error();
            return false;
        }
        return $this->store_result($result);
    }

    function multi_query($query) {
        $this->_result = sqlsrv_query($this->_link, $query);
        $this->error = "";
        if (!$this->_result) {
            $this->_get_error();
            return false;
        }
        return true;
    }

    function store_result($result = null) {
        if (!$result) {
            $result = $this->_result;
        }
        if (!$result) {
            return false;
        }
        if (sqlsrv_field_metadata($result)) {
            return new Result($result);
        }
        $this->affected_rows = sqlsrv_rows_affected($result);
        return true;
    }

    function next_result() {
        return $this->_result ? sqlsrv_next_result($this->_result) : null;
    }

    function result($query, $field = 0) {
        $result = $this->query($query);
        if (!is_object($result)) {
            return false;
        }
        $row = $result->fetch_row();
        return $row[$field];
    }
}
