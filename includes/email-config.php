<?php
// Load environment variables from .env file if not already loaded
if (!defined('ENV_LOADED')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configuring SMTP settings using environment variables
return [
    'host' => getenv('SMTP_HOST'),
    'port' => getenv('SMTP_PORT'),
    'username' => getenv('SMTP_USERNAME'),
    'password' => getenv('SMTP_PASSWORD'),
    'encryption' => getenv('SMTP_ENCRYPTION'),
];
