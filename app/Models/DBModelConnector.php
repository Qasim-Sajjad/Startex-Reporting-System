<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\ClientDatabaseManager;

class DBModelConnector extends Model
{
    /**
     * Override the constructor to set the dynamic connection.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the connection dynamically
        if (session()->has('client_database')) {
            $databaseName = session('client_database');
            ClientDatabaseManager::setConnection($databaseName);
            $this->setConnection('client');
        }
    }
}
