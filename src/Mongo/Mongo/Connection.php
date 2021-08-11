<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Lagdo\Adminer\Drivers\AbstractConnection;

use MongoClient;
use Exception;

class Connection extends AbstractConnection
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $last_id;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $_db;

    /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        $this->server_info = MongoClient::VERSION;

        try {
            $this->client = new MongoClient($server, $options);
            if ($options["password"] != "") {
                $options["password"] = "";
                try {
                    new MongoClient($server, $options);
                    $this->error = $this->adminer->lang('Database does not support password.');
                } catch (Exception $e) {
                    // this is what we want
                }
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function query($query, $unbuffered = false)
    {
        return false;
    }

    public function result($query, $field = 0)
    {
        return false;
    }

    public function select_db($database)
    {
        try {
            $this->_db = $this->client->selectDB($database);
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }
    }
}
