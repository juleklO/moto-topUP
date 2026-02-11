<?php
// src/public/index.php

require_once __DIR__ . '/../config/database.php';
define('APP_ROOT', dirname(__DIR__));

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$is_htmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

switch ($uri) {
  case '/':
    header('Content-Type: text/html; charset=UTF-8');
    require APP_ROOT . '/views/layout.php';
    break;

  case '/dashboard':
    header('Content-Type: text/html; charset=UTF-8');
    if (file_exists(APP_ROOT . '/views/partials/dashboard_fragment.php')) {
        require APP_ROOT . '/views/partials/dashboard_fragment.php';
    } else {
        require APP_ROOT . '/views/partials/dashboard.php';
    }
    break;

  case '/vehicle/create': 
  case '/api/vehicle/create': 
    if ($method === 'GET') {
      header('Content-Type: text/html; charset=UTF-8');
      require APP_ROOT . '/views/partials/form_vehicle.php';
    } 
    elseif ($method === 'POST') {
      require_once APP_ROOT . '/controllers/VehicleController.php';
      $input = $_POST;
      $created = VehicleController::create($pdo, $input);
      header('HX-Trigger: refreshVehicleList');
      require APP_ROOT . '/views/partials/dashboard.php';
    }
    break;

  case '/log/add':
  case '/api/submit-log': 
    require_once APP_ROOT . '/controllers/LogController.php';
    $controller = new LogController($pdo);
    $controller->add(); 
    break;
    
  case '/log/create': 
  case '/api/log/create':
    header('Content-Type: text/html; charset=UTF-8');
    $vehicleId = $_GET['vehicle_id'] ?? null;
    $vehicle = null;
    
    // The query is only run if the Regex check passes in the model
    if ($vehicleId) {
        require_once APP_ROOT . '/models/VehicleModel.php';
        $vehicle = VehicleModel::getById($pdo, $vehicleId);
    }
    
    // Graceful visual failure instead of PHP crash
    if (!$vehicle) {
        echo '<div class="p-4 text-red-500 bg-red-100 rounded-md shadow-sm">Ghost request intercepted: Valid UUID required to log refuel.</div>';
        break;
    }
    require APP_ROOT . '/views/partials/form_log.php';
    break;

  case '/service/add':
    require_once APP_ROOT . '/controllers/ServiceController.php';
    $controller = new ServiceController($pdo);
    $controller->create();
    break;

  case '/service/create':
    header('Content-Type: text/html; charset=UTF-8');
    require_once APP_ROOT . '/controllers/ServiceController.php';
    $controller = new ServiceController($pdo);
    $controller->create();
    break;

  case '/api/stats':
    require_once APP_ROOT . '/controllers/LogController.php';
    $controller = new LogController($pdo);
    $controller->getEfficiency(); 
    break;

  case '/api/vehicles':
    header('Content-Type: application/json; charset=UTF-8');
    require_once APP_ROOT . '/controllers/VehicleController.php';
    echo json_encode(VehicleController::getAll($pdo));
    break;

  default:
    http_response_code(404);
    echo $is_htmx 
        ? '<div class="p-4 text-red-500">404: Route Not Found</div>' 
        : json_encode(['error' => 'Not Found']);
    break;
}