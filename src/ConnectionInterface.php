<?php

namespace Lagdo\Adminer\Drivers\Pgsql;

interface ConnectionInterface
{
    public function quote($string);
}
