<?php

namespace Lagdo\Adminer\Drivers;

abstract class AbstractConnection implements ConnectionInterface
{
    use ConnectionTrait;

    /**
     * Undocumented variable
     *
     * @var mixed
     */
    protected $_result;

    /**
     * The server description
     *
     * @var string
     */
    protected $server_info;

    /**
     * The client object used to query the database server
     *
     * @var mixed
     */
    protected $client;

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
     * Return a quoted string
     *
     * @param string $string
     *
     * @return string
     */
    public function quote($string)
    {
        return $string;
    }

    /**
     * Get the client
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get the number of rows affected by the last query
     *
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->affected_rows;
    }
}
