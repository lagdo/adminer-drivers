<?php

namespace Lagdo\Adminer\Drivers;

interface ConnectionInterface
{
    /**
     * Get the extension name
     *
     * @return string
     */
    public function getExtension();

    /**
     * Get the server description
     *
     * @return string
     */
    public function getServerInfo();

    /**
     * Get the number of rows affected by the last query
     *
     * @return int
     */
    public function getAffectedRows();

    /**
     * Set the current database
     *
     * @param string $database
     *
     * @return boolean
     */
    public function select_db($database);

    /**
     * Query the current database
     *
     * @param string $query
     * @param boolean $unbuffered
     *
     * @return mixed
     */
    public function query($query, $unbuffered = false);

    /**
     * Return a quoted string
     *
     * @param string $string
     *
     * @return string
     */
    public function quote($string);

    /**
     * Open a connection to a server
     *
     * @param string $server    The server address, name or uri
     * @param array  $options   The connection options
     *
     * @return mixed
     */
    public function connect($server, array $options);

    /**
     * Get the client
     *
     * @return mixed
     */
    public function getClient();
}
