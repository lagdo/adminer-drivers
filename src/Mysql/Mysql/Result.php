<?php

namespace Lagdo\Adminer\Drivers\Mysql\Mysql;

class Result {
    var
        $num_rows, ///< @var int number of rows in the result
        $_result, $_offset = 0 ///< @access private
    ;

    /** Constructor
    * @param resource
    */
    public function __construct($result) {
        $this->_result = $result;
        $this->num_rows = mysql_num_rows($result);
    }

    /** Fetch next row as associative array
    * @return array
    */
    public function fetch_assoc() {
        return mysql_fetch_assoc($this->_result);
    }

    /** Fetch next row as numbered array
    * @return array
    */
    public function fetch_row() {
        return mysql_fetch_row($this->_result);
    }

    /** Fetch next field
    * @return object properties: name, type, orgtable, orgname, charsetnr
    */
    public function fetch_field() {
        $return = mysql_fetch_field($this->_result, $this->_offset++); // offset required under certain conditions
        $return->orgtable = $return->table;
        $return->orgname = $return->name;
        $return->charsetnr = ($return->blob ? 63 : 0);
        return $return;
    }

    /** Free result set
    */
    public function __destruct() {
        mysql_free_result($this->_result);
    }
}
