<?php

namespace App\Traits;

trait DynamicConnection
{
    /**
     * Set the database connection for the model dynamically.
     *
     * @param string $connectionName
     * @return void
     */
    public static function setDynamicConnection(string $connectionName)
    {
        $instance = new static;
        $instance->setConnection($connectionName);
    }
}
