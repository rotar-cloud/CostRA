<?php

declare(strict_types=1);

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '5432',
    'dbname' => getenv('DB_NAME') ?: 'awd_li',
    'user' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
];