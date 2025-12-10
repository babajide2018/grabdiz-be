<?php
// Test if Laravel API is accessible
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth/login';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['HTTPS'] = 'off';

// Change to Laravel's public directory
chdir(__DIR__ . '/api/public');
require __DIR__ . '/api/public/index.php';
