<?php

declare(strict_types=1);

use App\Services\AuthService;

require_once __DIR__ . '/../vendor/autoload.php';

$auth = new AuthService();

$email = 'admin@example.com';
$password = 'wrong-password';

if ($auth->login($email, $password)) {
    echo "Login successful." . PHP_EOL;

    $user = $auth->user();

    echo "Logged user: " . $user['name'] . PHP_EOL;
    echo "Role: " . $user['role_name'] . PHP_EOL;
    echo "Company: " . $user['company_name'] . PHP_EOL;
} else {
    echo "Invalid credentials." . PHP_EOL;
}