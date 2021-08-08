<?php

namespace Lagdo\Adminer\Drivers;

interface InputInterface
{
    /**
     * Get the query table name (to be created)
     * $_GET["create"]
     *
     * @return string
     */
    public function create();

    /**
     * Get the query trigger name
     * $_GET["trigger"]
     *
     * @return string
     */
    public function trigger();

    /**
     * Get the select query fields
     * $_GET["select"]
     *
     * @return array
     */
    public function select();

    /**
     * Get the query filters
     * $_GET["where"]
     *
     * @return array
     */
    public function where();

    /**
     * Get the query limit
     * $_GET["limit"]
     *
     * @return int
     */
    public function limit();

    /**
     * Get the query fields
     * $_POST["fields"]
     *
     * @return array
     */
    public function fields();

    /**
     * Get the auto increment step
     * $_POST["Auto_increment"], formatted with $this->adminer->number()
     *
     * @return string
     */
    public function autoIncrementStep();

    /**
     * Get the auto increment field
     * $_POST["auto_increment_col"]
     *
     * @return string
     */
    public function autoIncrementField();

    /**
     * Get the ??
     * $_POST["check"]
     *
     * @return array
     */
    public function checks();

    /**
     * Get the ??
     * $_POST["overwrite"]
     *
     * @return bool
     */
    public function overwrite();
}
