<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Mssql;

class Result {
    var $_result, $_offset = 0, $_fields, $num_rows;

    function __construct($result) {
        $this->_result = $result;
        $this->num_rows = mssql_num_rows($result);
    }

    function fetch_assoc() {
        return mssql_fetch_assoc($this->_result);
    }

    function fetch_row() {
        return mssql_fetch_row($this->_result);
    }

    function num_rows() {
        return mssql_num_rows($this->_result);
    }

    function fetch_field() {
        $return = mssql_fetch_field($this->_result);
        $return->orgtable = $return->table;
        $return->orgname = $return->name;
        return $return;
    }

    function seek($offset) {
        mssql_data_seek($this->_result, $offset);
    }

    function __destruct() {
        mssql_free_result($this->_result);
    }
}
