<?php
// src/models/VehicleModel.php

class VehicleModel {
    public static function getAll(PDO $pdo): array {
        $stmt = $pdo->query("
            SELECT * FROM vehicles 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }

public static function create(PDO $pdo, array $data): array {
    // Validation
    $name  = $data['name']  ?? null;
    $make  = $data['make']  ?? null;
    $model = $data['model'] ?? null;
    $year  = $data['year']  ?? null;

    if (!$name || !$make || !$model || !$year) {
        http_response_code(400);
        return ['error' => 'Missing required fields: name, make, model, year'];
    }

    $systemOfMeasurement = $data['system_of_measurement'] ?? 'metric';
    $correctionFactor    = $data['odometer_correction_factor'] ?? 1.0;
    
    $settings = json_encode([
        'ui_theme' => 'dark',
        'dashboard_layout' => ['graph', 'list']
    ]);

    // FIX 1: Explicitly cast custom ENUM and JSONB types in the SQL
    $stmt = $pdo->prepare("
        INSERT INTO vehicles (
            name, make, model, year, 
            vin, license_plate, fuel_capacity_liters, 
            system_of_measurement, odometer_correction_factor, settings
        )
        VALUES (
            :name, :make, :model, :year, 
            :vin, :license_plate, :fuel_capacity_liters, 
            :system_of_measurement::system_of_measurement, 
            :odometer_correction_factor, 
            :settings::jsonb
        )
        RETURNING *
    ");

    $stmt->execute([
        ':name'            => $name,
        ':make'            => $make,
        ':model'           => $model,
        ':year'            => (int)$year,
        ':vin'             => $data['vin'] ?? null,
        ':license_plate'   => $data['plate'] ?? null, 
        ':fuel_capacity_liters' => $data['fuel_capacity'] ?? 0.0,
        ':system_of_measurement' => $systemOfMeasurement,
        ':odometer_correction_factor' => $correctionFactor,
        ':settings'        => $settings
    ]);

    $vehicle = $stmt->fetch();

    // FIX 2: Save the 'initial_odometer' as a baseline fuel entry
    if (!empty($data['initial_odometer']) && $vehicle) {
        $stmtLog = $pdo->prepare("
            INSERT INTO fuel_entries (vehicle_id, odometer_reading, fuel_volume_liters, is_full, notes)
            VALUES (:vid, :odo, 0, false, 'Initial Odometer Reading')
        ");
        $stmtLog->execute([
            ':vid' => $vehicle['id'],
            ':odo' => (float)$data['initial_odometer']
        ]);
    }

    return $vehicle;
}

    public static function getById(PDO $pdo, string $id): ?array {
        // THE FIX: Intercept ghost requests (like "5") before they reach Postgres
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return null; // Reject silently, prevent the fatal DB crash
        }

        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}