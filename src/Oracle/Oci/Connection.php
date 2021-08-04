<?php

namespace Lagdo\Adminer\Drivers\Oracle\Oci;

use Lagdo\Adminer\Drivers\ConnectionInterface;

/**
 * Oracle driver to be used with the oci8 PHP extension.
 */
class Connection implements ConnectionInterface
{
    /**
     * The extension name
     *
     * @var string
     */
    protected $extension = "oci8";

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $_link;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $_result;

    /**
     * The server description
     *
     * @var string
     */
    protected $server_info;

    /**
     * Undocumented variable
     *
     * @var int
     */
    protected $affected_rows;

    /**
     * Undocumented variable
     *
     * @var int
     */
    protected $errno;

    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $error;

    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $_current_db;

    protected function _error($errno, $error) {
        if (ini_bool("html_errors")) {
            $error = html_entity_decode(strip_tags($error));
        }
        $error = preg_replace('~^[^:]*: ~', '', $error);
        $this->error = $error;
    }

    public function connect($server, $username, $password) {
        $this->_link = @oci_new_connect($username, $password, $server, "AL32UTF8");
        if ($this->_link) {
            $this->server_info = oci_server_version($this->_link);
            return true;
        }
        $error = oci_error();
        $this->error = $error["message"];
        return false;
    }

    public function quote($string) {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    public function select_db($database) {
        $this->_current_db = $database;
        return true;
    }

    public function query($query, $unbuffered = false) {
        $result = oci_parse($this->_link, $query);
        $this->error = "";
        if (!$result) {
            $error = oci_error($this->_link);
            $this->errno = $error["code"];
            $this->error = $error["message"];
            return false;
        }
        set_error_handler(array($this, '_error'));
        $return = @oci_execute($result);
        restore_error_handler();
        if ($return) {
            if (oci_num_fields($result)) {
                return new Statement($result);
            }
            $this->affected_rows = oci_num_rows($result);
            oci_free_statement($result);
        }
        return $return;
    }

    public function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    public function store_result() {
        return $this->_result;
    }

    public function next_result() {
        return false;
    }

    public function result($query, $field = 1) {
        $result = $this->query($query);
        if (!is_object($result) || !oci_fetch($result->_result)) {
            return false;
        }
        return oci_result($result->_result, $field);
    }
}
