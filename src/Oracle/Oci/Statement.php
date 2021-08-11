<?php

namespace Lagdo\Adminer\Drivers\Oracle\Oci;

use stdClass;

class Statement
{
    public $_result;
    public $_offset = 1;
    public $num_rows;

    public function __construct($result)
    {
        $this->_result = $result;
    }

    public function _convert($row)
    {
        foreach ((array) $row as $key => $val) {
            if (is_a($val, 'OCI-Lob')) {
                $row[$key] = $val->load();
            }
        }
        return $row;
    }

    public function fetch_assoc()
    {
        return $this->_convert(oci_fetch_assoc($this->_result));
    }

    public function fetch_row()
    {
        return $this->_convert(oci_fetch_row($this->_result));
    }

    public function fetch_field()
    {
        $column = $this->_offset++;
        $return = new stdClass;
        $return->name = oci_field_name($this->_result, $column);
        $return->orgname = $return->name;
        $return->type = oci_field_type($this->_result, $column);
        $return->charsetnr = (preg_match("~raw|blob|bfile~", $return->type) ? 63 : 0); // 63 - binary
        return $return;
    }

    public function __destruct()
    {
        oci_free_statement($this->_result);
    }
}
