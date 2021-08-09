<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite2;

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
     * @var int
     */
    public $num_rows;

    public function __construct($result)
    {
        $this->_result = $result;
        if (method_exists($result, 'numRows')) { // not available in unbuffered query
            $this->num_rows = $result->numRows();
        }
    }

    public function fetch_assoc()
    {
        $row = $this->_result->fetch(SQLITE_ASSOC);
        if (!$row) {
            return false;
        }
        $return = [];
        foreach ($row as $key => $val) {
            $return[($key[0] == '"' ? $this->server->idf_unescape($key) : $key)] = $val;
        }
        return $return;
    }

    public function fetch_row()
    {
        return $this->_result->fetch(SQLITE_NUM);
    }

    public function fetch_field()
    {
        $name = $this->_result->fieldName($this->_offset++);
        $pattern = '(\[.*]|"(?:[^"]|"")*"|(.+))';
        if (preg_match("~^($pattern\\.)?$pattern\$~", $name, $match)) {
            $table = ($match[3] != "" ? $match[3] : $this->server->idf_unescape($match[2]));
            $name = ($match[5] != "" ? $match[5] : $this->server->idf_unescape($match[4]));
        }
        return (object) array(
            "name" => $name,
            "orgname" => $name,
            "orgtable" => $table,
        );
    }
}
