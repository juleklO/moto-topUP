<?php
// src/models/LogModel.php

class LogModel {
    public static function add(PDO $pdo, array $data): array {
        $vehicle_id = $data['vehicle_id'] ?? null;
        $odometer   = $data['odometer'] ?? null;
        $fuel_volume= $data['fuel_volume'] ?? null;

        if (!$vehicle_id || $odometer === null || !$fuel_volume) {
            http_response_code(400);
            return ['error' => 'Missing required fields: vehicle_id, odometer, fuel_volume'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO fuel_logs (
                vehicle_id, odometer, fuel_volume, price_total, fuel_grade,
                is_full, missed_previous, station_location, notes
            )
            VALUES (
                :vehicle_id, :odometer, :fuel_volume, :price_total, :fuel_grade,
                :is_full, :missed_previous, :station_location, :notes
            )
            RETURNING *
        ");

        $stmt->execute([
            ':vehicle_id'       => (int)$vehicle_id,
            ':odometer'         => (int)$odometer,
            ':fuel_volume'      => (float)$fuel_volume,
            ':price_total'      => $data['price_total'] ?? null,
            ':fuel_grade'       => $data['fuel_grade'] ?? null,
            ':is_full'          => isset($data['is_full']) ? (bool)$data['is_full'] : true,
            ':missed_previous'  => isset($data['missed_previous']) ? (bool)$data['missed_previous'] : false,
            ':station_location' => $data['station_location'] ?? null,
            ':notes'            => $data['notes'] ?? null,
        ]);

        return $stmt->fetch();
    }

    public static function listEfficiency(PDO $pdo, int $vehicle_id): array {
        $stmt = $pdo->prepare("
            SELECT *
            FROM view_fuel_efficiency
            WHERE vehicle_id = :vehicle_id
            ORDER BY log_date DESC
        ");
        $stmt->execute([':vehicle_id' => $vehicle_id]);
        return $stmt->fetchAll();
    }
    public static function getLastOdometer(PDO $pdo, int $vehicle_id): ?int {
        $stmt = $pdo->prepare("
            SELECT odometer
            FROM fuel_logs
            WHERE vehicle_id = :vehicle_id
            ORDER BY log_date DESC
            LIMIT 1
        ");
        $stmt->execute([':vehicle_id' => $vehicle_id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['odometer'] : null;
    }
}