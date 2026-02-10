<?php
// src/controllers/ServiceController.php

require_once __DIR__ . '/../models/ServiceModel.php';
require_once __DIR__ . '/../models/VehicleModel.php';

class ServiceController {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $vehicleId = $_GET['vehicle_id'] ?? null;
            if (!$vehicleId) die("Vehicle ID missing");
            
            $vehicle = VehicleModel::getById($this->pdo, $vehicleId);
            $tasks = ServiceModel::getTasks($this->pdo);
            
            require APP_ROOT . '/views/partials/form_service.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Convert Imperial Inputs if necessary (simplified for brevity)
            $vehicleId = $_POST['vehicle_id'];
            $odo = (float)$_POST['odometer'];
            
            // Note: In a full implementation, check $vehicle['system_of_measurement'] 
            // and convert $odo to KM here like we did in LogController.
            
            $success = ServiceModel::add($this->pdo, [
                'vehicle_id'      => $vehicleId,
                'service_task_id' => $_POST['service_task_id'] ?? null,
                'performed_at'    => $_POST['performed_at'],
                'odometer'        => $odo,
                'cost'            => $_POST['cost'],
                'notes'           => $_POST['notes']
            ]);

            if ($success) {
                header("Location: /?success=service_logged&vehicle_id=$vehicleId");
            } else {
                header("Location: /?error=service_failed");
            }
            exit;
        }
    }
}