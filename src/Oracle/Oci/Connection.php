<?php

namespace Lagdo\Adminer\Drivers\Oracle\Oci;

use Lagdo\Adminer\Drivers\AbstractConnection;

/**
 * Oracle driver to be used with the oci8 PHP extension.
 */
class Connection extends AbstractConnection
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $_current_db;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'oci8';
    }

    public function _error($errno, $error) {
        if (ini_bool("html_errors")) {
            $error = html_entity_decode(strip_tags($error));
        }
        $error = preg_replace('~^[^:]*: ~', '', $error);
        $this->error = $error;
    }

     /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        $this->client = @oci_new_connect($username, $password, $server, "AL32UTF8");
        if ($this->client) {
            $this->server_info = oci_server_version($this->client);
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
        $result = oci_parse($this->client, $query);
        $this->error = "";
        if (!$result) {
            $error = oci_error($this->client);
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
