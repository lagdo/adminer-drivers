<?php

namespace Lagdo\Adminer\Drivers;

trait ConnectionTrait
{
    /**
     * @var AdminerInterface
     */
    protected $adminer;

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
     * Get the extension name
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
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
}
