<?php

namespace Lagdo\Adminer\Drivers\Elastic;

use Lagdo\Adminer\Drivers\ConnectionInterface;

use function Lagdo\Adminer\Drivers\lang;

class Connection implements ConnectionInterface
{
    var $extension = "JSON", $server_info, $errno, $error, $_url, $_db;

    /**
     * Performs query
     * @param string
     * @param array
     * @param string
     * @return mixed
     */
    public function rootQuery($path, $content = [], $method = 'GET') {
        @ini_set('track_errors', 1); // @ - may be disabled
        $file = @file_get_contents("$this->_url/" . ltrim($path, '/'), false, stream_context_create(array('http' => array(
            'method' => $method,
            'content' => $content === null ? $content : json_encode($content),
            'header' => 'Content-Type: application/json',
            'ignore_errors' => 1, // available since PHP 5.2.10
        ))));
        if (!$file) {
            $this->error = $php_errormsg;
            return $file;
        }
        if (!preg_match('~^HTTP/[0-9.]+ 2~i', $http_response_header[0])) {
            $this->error = lang('Invalid credentials.') . " $http_response_header[0]";
            return false;
        }
        $return = json_decode($file, true);
        if ($return === null) {
            $this->errno = json_last_error();
            if (function_exists('json_last_error_msg')) {
                $this->error = json_last_error_msg();
            } else {
                $constants = get_defined_constants(true);
                foreach ($constants['json'] as $name => $value) {
                    if ($value == $this->errno && preg_match('~^JSON_ERROR_~', $name)) {
                        $this->error = $name;
                        break;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Performs query relative to actual selected DB
     * @param string
     * @param array
     * @param string
     * @return mixed
     */
    public function query($path, $content = [], $method = 'GET') {
        return $this->rootQuery(($this->_db != "" ? "$this->_db/" : "/") . ltrim($path, '/'), $content, $method);
    }

    public function connect($server, $username, $password) {
        preg_match('~^(https?://)?(.*)~', $server, $match);
        $this->_url = ($match[1] ? $match[1] : "http://") . "$username:$password@$match[2]";
        $return = $this->query('');
        if ($return) {
            $this->server_info = $return['version']['number'];
        }
        return (bool) $return;
    }

    public function select_db($database) {
        $this->_db = $database;
        return true;
    }

    public function quote($string) {
        return $string;
    }

}
