<?php

namespace Lagdo\Adminer\Drivers;

use function Lagdo\Adminer\Drivers\h;
use function Lagdo\Adminer\Drivers\format_time;

class Query
{
    /**
     * Get the query table name (to be created)
     *
     * @return string
     */
    public function create()
    {
        // $_GET["create"]
    }

    /**
     * Get the query trigger name
     *
     * @return string
     */
    public function trigger()
    {
        // $_GET["trigger"]
    }

    /**
     * Get the select query fields
     *
     * @return array
     */
    public function select()
    {
        // $_GET["select"]
    }

    /**
     * Get the query filters
     *
     * @return array
     */
    public function where()
    {
        // $_GET["where"]
    }

    /**
     * Get the query limit
     *
     * @return int
     */
    public function limit()
    {
        // $_GET["limit"]
    }

    /**
     * Get the query fields
     *
     * @return array
     */
    public function fields()
    {
        // $_POST["fields"]
    }

    /**
     * Get the auto increment step
     *
     * @return string
     */
    public function autoIncrement()
    {
        // $_POST["Auto_increment"]
    }

    /**
     * Get the auto increment field
     *
     * @return string
     */
    public function autoIncrementField()
    {
        // $_POST["auto_increment_col"]
    }

    /**
     * Get the ??
     *
     * @return array
     */
    public function checks()
    {
        // $_POST["check"]
    }

    /**
     * Get the ??
     *
     * @return bool
     */
    public function overwrite()
    {
        // $_POST["overwrite"]
    }
}
