<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Db\MsSql\SqlSrv;

class Statement
{
    /**
     * Undocumented variable
     *
     * @var object
     */
    public $_result;

    /**
     * Undocumented variable
     *
     * @var int
     */
    public $_offset = 0;

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $_fields;

    /**
     * Undocumented variable
     *
     * @var int
     */
    public $num_rows;

    public function __construct($result)
    {
        $this->_result = $result;
        // $this->num_rows = sqlsrv_num_rows($result); // available only in scrollable results
    }

    public function _convert($row)
    {
        foreach ((array) $row as $key => $val) {
            if (is_a($val, 'DateTime')) {
                $row[$key] = $val->format("Y-m-d H:i:s");
            }
            //! stream
        }
        return $row;
    }

    public function fetch_assoc()
    {
        return $this->_convert(sqlsrv_fetch_array($this->_result, SQLSRV_FETCH_ASSOC));
    }

    public function fetch_row()
    {
        return $this->_convert(sqlsrv_fetch_array($this->_result, SQLSRV_FETCH_NUMERIC));
    }

    public function fetch_field()
    {
        if (!$this->_fields) {
            $this->_fields = sqlsrv_field_metadata($this->_result);
        }
        $field = $this->_fields[$this->_offset++];
        $return = new stdClass;
        $return->name = $field["Name"];
        $return->orgname = $field["Name"];
        $return->type = ($field["Type"] == 1 ? 254 : 0);
        return $return;
    }

    public function seek($offset)
    {
        for ($i=0; $i < $offset; $i++) {
            sqlsrv_fetch($this->_result); // SQLSRV_SCROLL_ABSOLUTE added in sqlsrv 1.1
        }
    }

    public function __destruct()
    {
        sqlsrv_free_stmt($this->_result);
    }
}
