<?php

namespace Lagdo\Adminer\Drivers\Db;

use Lagdo\Adminer\Drivers\AdminerInterface;

abstract class Connection implements ConnectionInterface
{
    use ConnectionTrait;

    /**
     * Undocumented variable
     *
     * @var mixed
     */
    public $_result;

    /**
     * The server description
     *
     * @var string
     */
    public $server_info;

    /**
     * Undocumented variable
     *
     * @var int
     */
    public $affected_rows;

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
     * @inheritDoc
     */
    public function set_charset($charset)
    {
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return false;
    }
}
