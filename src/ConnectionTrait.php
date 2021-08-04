<?php

namespace Lagdo\Adminer\Drivers;

trait ConnectionTrait
{
    /**
     * The extension name
     *
     * @var string
     */
    protected $extension;

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
}
