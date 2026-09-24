<?php

function db_config(): array
{
    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'name' => getenv('DB_NAME') ?: 'MythiFox',
    ];
}

function db_connect(): mysqli
{
    $config = db_config();

    $conn = new mysqli(
        $config['host'],
        $config['user'],
        $config['pass'],
        $config['name']
    );

    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    return $conn;
}