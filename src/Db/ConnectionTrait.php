<?php

namespace Lagdo\Adminer\Drivers\Db;

use Lagdo\Adminer\Drivers\AdminerDbInterface;
use Lagdo\Adminer\Drivers\AdminerUiInterface;

trait ConnectionTrait
{
    /**
     * @var AdminerDbInterface
     */
    protected $db;

    /**
     * @var AdminerUiInterface
     */
    protected $ui;

    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * The extension name
     *
     * @var string
     */
    public $extension;

    /**
     * The client object used to query the database server
     *
     * @var mixed
     */
    protected $client;

    /**
     * Get the extension name
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
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
     * Get the server description
     *
     * @return string
     */
    public function getServerInfo()
    {
        return $this->server_info;
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

    /**
     * Return a quoted string
     *
     * @param string $string
     *
     * @return string
     */
    public function quoteBinary($string)
    {
        return $this->quote($string);
    }

    /**
     * Convert value returned by database to actual value
     * @param string
     * @param array
     * @return string
     */
    public function value($val, $field)
    {
        return (is_resource($val) ? stream_get_contents($val) : $val);
    }

    /**
     * Get warnings about the last command
     * @return string
     */
    public function warnings()
    {
        return '';
    }
}
