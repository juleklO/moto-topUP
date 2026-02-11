<?php
// src/controllers/LogController.php

require_once __DIR__ . '/../models/LogModel.php';
require_once __DIR__ . '/../models/VehicleModel.php';

class LogController {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /');
            exit;
        }

        // 1. Sanitize & Retrieve Inputs
        $vehicleId = $_POST['vehicle_id'] ?? null;
        $filledAt  = $_POST['filled_at'] ?? null; // Capture Date
        $odometer  = filter_input(INPUT_POST, 'odometer', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $fuelVol   = filter_input(INPUT_POST, 'fuel_volume', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $price     = filter_input(INPUT_POST, 'price_total', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        $isFull     = isset($_POST['is_full']); 
        $missedFill = isset($_POST['missed_fill']); 
        $notes      = trim($_POST['notes'] ?? '');
        $station    = trim($_POST['station_location'] ?? '');

        // 2. Validation
        if (!$vehicleId || !$odometer || !$fuelVol) {
            header("Location: /?error=missing_fields"); 
            exit;
        }

        // 3. Fetch Vehicle Settings
        $vehicle = VehicleModel::getById($this->pdo, $vehicleId);
        if (!$vehicle) {
            header("Location: /?error=vehicle_not_found");
            exit;
        }

        // 4. Unit Conversion (Imperial Input -> Metric Storage)
        $system = $vehicle['system_of_measurement'] ?? 'metric';
        
        $odometerReading = (float)$odometer;
        $fuelVolumeLiters = (float)$fuelVol;

        if ($system === 'imperial_us') {
            $odometerReading = $odometerReading * 1.60934;
            $fuelVolumeLiters = $fuelVolumeLiters * 3.78541;
        } elseif ($system === 'imperial_uk') {
            $odometerReading = $odometerReading * 1.60934;
            $fuelVolumeLiters = $fuelVolumeLiters * 4.54609;
        }

        // 5. Persist to Database
        try {
            $logEntry = LogModel::add($this->pdo, [
                'vehicle_id'       => $vehicleId,
                'filled_at'        => $filledAt, // Pass to model
                'odometer'         => $odometerReading, 
                'fuel_volume'      => $fuelVolumeLiters,
                'price_total'      => $price,
                'is_full'          => $isFull,
                'missed_fill'      => $missedFill,
                'notes'            => $notes,
                'station_location' => $station
            ]);

            header("Location: /?success=log_added&vehicle_id=" . $vehicleId);
            exit;

        } catch (Exception $e) {
            error_log($e->getMessage());
            header("Location: /?error=db_error");
            exit;
        }
    }
    
    public function getEfficiency() {
        $vehicleId = $_GET['vehicle_id'] ?? null;
        if (!$vehicleId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing vehicle_id']);
            return;
        }
        $data = LogModel::getEfficiencyReport($this->pdo, $vehicleId);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}