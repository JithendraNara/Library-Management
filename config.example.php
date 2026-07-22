<?php
declare(strict_types=1);

/**
 * Central Library — configuration.
 * Copy config.example.php to config.php and adjust for your environment.
 */

return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'name'     => getenv('DB_NAME') ?: 'central_library',
        'user'     => getenv('DB_USER') ?: 'root',
        'pass'     => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],
    // Loan period in days (the original CLI said "return within 30 days").
    'loan_days' => 30,
];
