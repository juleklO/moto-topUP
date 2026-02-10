<?php
require_once __DIR__ . '/../config/database.php';

// Define app root for reliable includes from views/partials
define('APP_ROOT', dirname(__DIR__));

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$is_htmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

switch ($uri) {

  // ---------------------------------------------------------------------------
  // FULL PAGE
  // ---------------------------------------------------------------------------
  case '/':
    header('Content-Type: text/html; charset=UTF-8');
    require APP_ROOT . '/views/layout.php';
    break;

  // ---------------------------------------------------------------------------
  // DASHBOARD FRAGMENT (HTMX)
  // ---------------------------------------------------------------------------
  case '/dashboard':
    header('Content-Type: text/html; charset=UTF-8');
    require APP_ROOT . '/views/partials/dashboard_fragment.php';
    break;

  // ---------------------------------------------------------------------------
  // VEHICLE MANAGEMENT (HTMX)
  // ---------------------------------------------------------------------------
  case '/api/vehicle/create':
    header('Content-Type: text/html; charset=UTF-8');

    if ($method === 'GET') {
      require APP_ROOT . '/views/partials/form_vehicle.php';
      break;
    }

    if ($method === 'POST') {
      require_once APP_ROOT . '/controllers/VehicleController.php';

      // HTMX form posts as form-encoded by default:
      $input = $_POST;

      // Create (VehicleController::create should validate)
      $created = VehicleController::create($pdo, $input);

      // Optional: trigger an event (layout listens and can reload dashboard)
      header('HX-Trigger: refreshVehicleList');

      // After creation, return dashboard fragment
      require APP_ROOT . '/views/partials/dashboard_fragment.php';
      break;
    }

    http_response_code(405);
    echo "Method Not Allowed";
    break;

  // ---------------------------------------------------------------------------
  // STATS API (Chart.js fetch)
  // ---------------------------------------------------------------------------
  case '/api/stats':
    header('Content-Type: application/json; charset=UTF-8');

    $vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($vehicle_id <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'Missing or invalid id']);
      break;
    }

    require_once APP_ROOT . '/controllers/StatsController.php';
    echo json_encode(StatsController::getFuelHistory($pdo, $vehicle_id));
    break;

  // ---------------------------------------------------------------------------
  // FUEL LOG FORM (per-vehicle)
  // ---------------------------------------------------------------------------
  case '/api/log/create':
    header('Content-Type: text/html; charset=UTF-8');
    require APP_ROOT . '/views/partials/form_log.php';
    break;

  // ---------------------------------------------------------------------------
  // SUBMIT FUEL LOG (HTMX)
  // ---------------------------------------------------------------------------
  case '/api/submit-log':
    if ($method !== 'POST') {
      http_response_code(405);
      echo "Method Not Allowed";
      break;
    }

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

    require_once APP_ROOT . '/models/LogModel.php';

    LogModel::add($pdo, [
      'vehicle_id' => $vehicle_id,
      'odometer' => $odometer,
      'fuel_volume' => $liters,
      'is_full' => isset($_POST['is_full']),
      'missed_previous' => isset($_POST['missed_previous']),
    ]);

    header('Content-Type: text/html; charset=UTF-8');
    require APP_ROOT . '/views/partials/dashboard_fragment.php';
    break;

  // ---------------------------------------------------------------------------
  // KEEP: JSON API for vehicles (your existing endpoint)
  // ---------------------------------------------------------------------------
  case '/api/vehicles':
    header('Content-Type: application/json; charset=UTF-8');
    require_once APP_ROOT . '/controllers/VehicleController.php';

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

  default:
    http_response_code(404);

    if ($is_htmx) {
      header('Content-Type: text/html; charset=UTF-8');
      echo '<div class="bg-moto-panel border border-gray-700 p-4 rounded text-sm text-moto-dim">Not Found</div>';
    } else {
      header('Content-Type: application/json; charset=UTF-8');
      echo json_encode(['error' => 'Not Found']);
    }
    break;
}
