<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Lagdo\Adminer\Drivers\AbstractConnection;

use function Lagdo\Adminer\Drivers\lang;

class Connection extends AbstractConnection
{
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
    protected $_db;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'Mongo';
        $this->server_info = MongoClient::VERSION;
    }

     /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        try {
            $this->client = new MongoClient($server, $options);
            if ($options["password"] != "") {
                $options["password"] = "";
                try {
                    new MongoClient($server, $options);
                    $this->error = lang('Database does not support password.');
                } catch (Exception $e) {
                    // this is what we want
                }
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function query($query, $unbuffered = false) {
        return false;
    }

    public function select_db($database) {
        try {
            $this->_db = $this->client->selectDB($database);
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }
    }
}
