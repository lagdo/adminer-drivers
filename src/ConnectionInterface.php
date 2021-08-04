<?php

namespace Lagdo\Adminer\Drivers;

interface ConnectionInterface
{
    public function quote($string);
}
