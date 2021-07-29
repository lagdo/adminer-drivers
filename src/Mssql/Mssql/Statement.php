<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Mssql;

class Statement {
    var $_result, $_offset = 0, $_fields, $num_rows;

    public function __construct($result) {
        $this->_result = $result;
        $this->num_rows = mssql_num_rows($result);
    }

    public function fetch_assoc() {
        return mssql_fetch_assoc($this->_result);
    }

    public function fetch_row() {
        return mssql_fetch_row($this->_result);
    }

    public function num_rows() {
        return mssql_num_rows($this->_result);
    }

    public function fetch_field() {
        $return = mssql_fetch_field($this->_result);
        $return->orgtable = $return->table;
        $return->orgname = $return->name;
        return $return;
    }

    public function seek($offset) {
        mssql_data_seek($this->_result, $offset);
    }

    public function __destruct() {
        mssql_free_result($this->_result);
    }
}
