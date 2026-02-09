<?php
// src/public/index.php

require_once __DIR__ . '/../config/database.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

switch ($uri) {
    case '/api/vehicles':
        require_once __DIR__ . '/../controllers/VehicleController.php';
        if ($method === 'GET') {
            echo json_encode(VehicleController::getAll($pdo));
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(VehicleController::create($pdo, $input));
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
        }
        break;

    case '/api/logs':
        require_once __DIR__ . '/../controllers/LogController.php';
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(LogController::add($pdo, $input));
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}