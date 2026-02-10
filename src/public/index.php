<?php
require_once __DIR__ . '/../config/database.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// HTMX sets header: HX-Request: true
$is_htmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

switch ($uri) {
  // ---------------------------------------------------------------------------
  // Frontend (HTML)
  // ---------------------------------------------------------------------------
  case '/':
    header('Content-Type: text/html; charset=UTF-8');

    // Full page load: layout includes dashboard partial
    require __DIR__ . '/../views/layout.php';
    break;

  case '/api/add-log-form':
    header('Content-Type: text/html; charset=UTF-8');

    // HTMX fragment: return form only
    require __DIR__ . '/../views/partials/form_log.php';
    break;

  case '/api/submit-log':
    if ($method !== 'POST') {
      http_response_code(405);
      header('Content-Type: text/plain; charset=UTF-8');
      echo "Method Not Allowed";
      break;
    }

    // HTMX posts form-encoded by default
    $vehicle_id = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    $odometer   = isset($_POST['odometer']) ? (int)$_POST['odometer'] : null;
    $liters     = isset($_POST['liters']) ? (float)$_POST['liters'] : null;

    if ($vehicle_id <= 0 || $odometer === null || !$liters || $liters <= 0) {
      http_response_code(400);
      header('Content-Type: text/html; charset=UTF-8');
      echo '<div class="bg-black border border-moto-alert p-4 rounded text-sm">
              <span class="text-moto-alert font-bold">Error:</span> Missing or invalid fields.
            </div>';
      break;
    }

    require_once __DIR__ . '/../models/LogModel.php';

    // Map form -> DB columns (fuel_volume stores liters here)
    LogModel::add($pdo, [
      'vehicle_id' => $vehicle_id,
      'odometer' => $odometer,
      'fuel_volume' => $liters,
      'is_full' => isset($_POST['is_full']),
      'missed_previous' => isset($_POST['missed_previous']),
    ]);

    // Return updated dashboard fragment
    header('Content-Type: text/html; charset=UTF-8');
    require __DIR__ . '/../views/partials/dashboard.php';
    break;

  // ---------------------------------------------------------------------------
  // Existing JSON API (kept)
  // ---------------------------------------------------------------------------
  case '/api/vehicles':
    header('Content-Type: application/json');
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
    header('Content-Type: application/json');
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

    // Return HTML for HTMX calls, JSON otherwise (simple rule)
    if ($is_htmx || $uri === '/') {
      header('Content-Type: text/html; charset=UTF-8');
      echo '<div class="bg-black border border-gray-700 p-4 rounded text-sm text-gray-400">
              Not Found
            </div>';
    } else {
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Not Found']);
    }
    break;
}