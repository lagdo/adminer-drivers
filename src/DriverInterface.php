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
}
