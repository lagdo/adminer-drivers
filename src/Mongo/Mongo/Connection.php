<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Lagdo\Adminer\Drivers\ConnectionInterface;

class Connection implements ConnectionInterface
{
    var $extension = "Mongo", $server_info = MongoClient::VERSION, $error, $last_id, $_link, $_db;

    public function connect($uri, $options) {
        try {
            $this->_link = new MongoClient($uri, $options);
            if ($options["password"] != "") {
                $options["password"] = "";
                try {
                    new MongoClient($uri, $options);
                    $this->error = lang('Database does not support password.');
                } catch (Exception $e) {
                    // this is what we want
                }
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function query($query) {
        return false;
    }

    public function select_db($database) {
        try {
            $this->_db = $this->_link->selectDB($database);
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }
    }

    public function quote($string) {
        return $string;
    }
}
