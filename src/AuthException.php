<?php

namespace Lagdo\Adminer\Drivers;

use Exception;

use function Lagdo\Adminer\Drivers\h;

class AuthException extends Exception
{
    /**
     * The constructor
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct(h($message));
    }
}
