<?php
// src/models/VehicleModel.php

class VehicleModel {
    /**
     * Fetch all vehicles.
     * Note: We no longer filter by 'is_active' as it wasn't in the new schema,
     * but we sort by creation date.
     */
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

        // Map inputs to new Schema fields
        // Defaulting to Metric and 1.0 correction factor if not provided
        $systemOfMeasurement = $data['system_of_measurement'] ?? 'metric';
        $correctionFactor    = $data['odometer_correction_factor'] ?? 1.0;
        
        // Default settings JSON
        $settings = json_encode([
            'ui_theme' => 'dark',
            'dashboard_layout' => ['graph', 'list']
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                name, make, model, year, 
                vin, license_plate, fuel_capacity_liters, 
                system_of_measurement, odometer_correction_factor, settings
            )
            VALUES (
                :name, :make, :model, :year, 
                :vin, :license_plate, :fuel_capacity_liters, 
                :system_of_measurement, :odometer_correction_factor, :settings
            )
            RETURNING *
        ");

        $stmt->execute([
            ':name'            => $name,
            ':make'            => $make,
            ':model'           => $model,
            ':year'            => (int)$year,
            ':vin'             => $data['vin'] ?? null,
            ':license_plate'   => $data['plate'] ?? null, // Form field 'plate' -> DB 'license_plate'
            ':fuel_capacity_liters' => $data['fuel_capacity'] ?? 0.0,
            ':system_of_measurement' => $systemOfMeasurement,
            ':odometer_correction_factor' => $correctionFactor,
            ':settings'        => $settings
        ]);

        return $stmt->fetch();
    }

    /**
     * Get a single vehicle by UUID
     */
    public static function getById(PDO $pdo, string $id): ?array {
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}