<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Mssql;

use Lagdo\Adminer\Drivers\ConnectionInterface;

class Connection implements ConnectionInterface
{
    var $extension = "MSSQL", $_link, $_result, $server_info, $affected_rows, $error;

    public function connect($server, $username, $password) {
        $this->_link = @mssql_connect($server, $username, $password);
        if ($this->_link) {
            $result = $this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");
            if ($result) {
                $row = $result->fetch_row();
                $this->server_info = $this->result("sp_server_info 2", 2) . " [$row[0]] $row[1]";
            }
        } else {
            $this->error = mssql_get_last_message();
        }
        return (bool) $this->_link;
    }

    public function quote($string) {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    public function select_db($database) {
        return mssql_select_db($database);
    }

    public function query($query, $unbuffered = false) {
        $result = @mssql_query($query, $this->_link); //! $unbuffered
        $this->error = "";
        if (!$result) {
            $this->error = mssql_get_last_message();
            return false;
        }
        if ($result === true) {
            $this->affected_rows = mssql_rows_affected($this->_link);
            return true;
        }
        return new Result($result);
    }

    public function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    public function store_result() {
        return $this->_result;
    }

    public function next_result() {
        return mssql_next_result($this->_result->_result);
    }

    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!is_object($result)) {
            return false;
        }
        return mssql_result($result->_result, 0, $field);
    }
}
