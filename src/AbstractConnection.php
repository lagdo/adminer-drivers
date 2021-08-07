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
    public $server_info;

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
    public $errno;

    /**
     * Undocumented variable
     *
     * @var string
     */
    public $error;

    /**
     * The constructor
     *
     * @param AdminerInterface
     * @param ServerInterface
     * @param string
     */
    public function __construct(AdminerInterface $adminer, ServerInterface $server, string $extension)
    {
        $this->adminer = $adminer;
        $this->server = $server;
        $this->extension = $extension;
    }

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
}
