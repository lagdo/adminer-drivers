<?php

namespace Lagdo\Adminer\Drivers\MongoDb;

class Statement
{
    /**
     * Undocumented variable
     *
     * @var int
     */
    public $num_rows;

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $_rows = [];

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
    public $_charset = [];

    public function __construct($result)
    {
        foreach ($result as $item) {
            $row = [];
            foreach ($item as $key => $val) {
                if (is_a($val, 'MongoDB\BSON\Binary')) {
                    $this->_charset[$key] = 63;
                }
                $row[$key] =
                    (is_a($val, 'MongoDB\BSON\ObjectID') ? 'MongoDB\BSON\ObjectID("' . "$val\")" :
                    (is_a($val, 'MongoDB\BSON\UTCDatetime') ? $val->toDateTime()->format('Y-m-d H:i:s') :
                    (is_a($val, 'MongoDB\BSON\Binary') ? $val->getData() : //! allow downloading
                    (is_a($val, 'MongoDB\BSON\Regex') ? "$val" :
                    (
                        is_object($val) || is_array($val) ? json_encode($val, 256) : // 256 = JSON_UNESCAPED_UNICODE
                    $val // MongoMinKey, MongoMaxKey
                    )))));
            }
            $this->_rows[] = $row;
            foreach ($row as $key => $val) {
                if (!isset($this->_rows[0][$key])) {
                    $this->_rows[0][$key] = null;
                }
            }
        }
        $this->num_rows = count($this->_rows);
    }

    public function fetch_assoc()
    {
        $row = current($this->_rows);
        if (!$row) {
            return $row;
        }
        $return = [];
        foreach ($this->_rows[0] as $key => $val) {
            $return[$key] = $row[$key];
        }
        next($this->_rows);
        return $return;
    }

    public function fetch_row()
    {
        $return = $this->fetch_assoc();
        if (!$return) {
            return $return;
        }
        return array_values($return);
    }

    public function fetch_field()
    {
        $keys = array_keys($this->_rows[0]);
        $name = $keys[$this->_offset++];
        return (object) array(
            'name' => $name,
            'charsetnr' => $this->_charset[$name],
        );
    }
}
