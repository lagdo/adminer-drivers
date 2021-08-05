<?php

namespace Lagdo\Adminer\Drivers;

interface AdminerInterface
{
    /**
     * Translate string
     *
     * @param string
     * @param int
     *
     * @return string
     */
    public function lang($idf, $number = null);

    /**
     * Get the database user credentials
     *
     * @return array
     */
    public function credentials();

    /**
     * Get the operators supported by the database
     *
     * @return array
     */
    public function operators();

    /**
     * Get SSL connection options
     *
     * @return array
     */
    public function connectSsl();

    /**
     * Select data from table
     *
     * @param array $select
     * @param array $where
     * @param array $group
     * @param array $order
     * @param int $limit
     * @param int $page
     *
     * @return string
     */
    public function buildSelectQuery(array $select, array $where, array $group, array $order = [], $limit = 1, $page = 0);
}
