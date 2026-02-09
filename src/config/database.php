<?php
// src/config/database.php

$host = getenv('DATABASE_HOST') ?: 'db';
$db   = getenv('DATABASE_NAME') ?: 'moto';
$user = getenv('DATABASE_USER') ?: 'moto_user';
$pass = getenv('DATABASE_PASS') ?: '';

$dsn = "pgsql:host={$host};dbname={$db};options='--client_encoding=UTF8'";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}