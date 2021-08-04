<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Lagdo\Adminer\Drivers\ConnectionInterface;

use function Lagdo\Adminer\Drivers\lang;

class Connection implements ConnectionInterface
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $extension = "Mongo";

    /**
     * The server description
     *
     * @var string
     */
    protected $server_info = MongoClient::VERSION;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $error;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $last_id;

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
    protected $_db;

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
