<?php

declare(strict_types=1);

return [
    'host'     => getenv('MYSQL_HOST') ?: 'localhost',
    'port'     => (int) (getenv('MYSQL_PORT') ?: '3306'),
    'user'     => getenv('MYSQL_USER') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'database' => getenv('MYSQL_DATABASE') ?: 'moaz-carvalue',
];
