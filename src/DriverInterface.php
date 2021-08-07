<?php

namespace Lagdo\Adminer\Drivers;

interface DriverInterface
{
    /**
     * Get the current query
     *
     * @return Query
     */
    public function getQuery();

    /**
     * Return a quoted string
     *
     * @param string $string
     *
     * @return string
     */
    public function quoteBinary($string);
}
