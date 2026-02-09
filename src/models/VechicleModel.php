<?php
// src/models/VehicleModel.php

class VehicleModel {
    public static function getAll(PDO $pdo): array {
        $stmt = $pdo->query("SELECT * FROM vehicles WHERE is_active = TRUE ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function create(PDO $pdo, array $data): array {
        // Minimal required fields per schema
        $name  = $data['name']  ?? null;
        $make  = $data['make']  ?? null;
        $model = $data['model'] ?? null;
        $year  = $data['year']  ?? null;

        if (!$name || !$make || !$model || !$year) {
            http_response_code(400);
            return ['error' => 'Missing required fields: name, make, model, year'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (name, make, model, year, vin, plate, fuel_capacity, initial_odometer)
            VALUES (:name, :make, :model, :year, :vin, :plate, :fuel_capacity, :initial_odometer)
            RETURNING *
        ");

        $stmt->execute([
            ':name'            => $name,
            ':make'            => $make,
            ':model'           => $model,
            ':year'            => (int)$year,
            ':vin'             => $data['vin'] ?? null,
            ':plate'           => $data['plate'] ?? null,
            ':fuel_capacity'   => $data['fuel_capacity'] ?? null,
            ':initial_odometer'=> $data['initial_odometer'] ?? 0,
        ]);

        return $stmt->fetch();
    }
}