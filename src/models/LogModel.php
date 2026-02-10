<?php
// src/models/LogModel.php

class LogModel {
    public static function add(PDO $pdo, array $data): array {
        $is_full = isset($data['is_full']) ? filter_var($data['is_full'], FILTER_VALIDATE_BOOLEAN) : true;
        $missed  = isset($data['missed_fill']) ? filter_var($data['missed_fill'], FILTER_VALIDATE_BOOLEAN) : false;
        $filledAt = !empty($data['filled_at']) ? $data['filled_at'] : date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO fuel_entries (
                vehicle_id, filled_at, odometer_reading, fuel_volume_liters, 
                total_cost, is_full, missed_fill, notes
            )
            VALUES (
                :vehicle_id, :filled_at, :odometer_reading, :fuel_volume_liters, 
                :total_cost, :is_full, :missed_fill, :notes
            )
            RETURNING *
        ");

        $stmt->bindValue(':vehicle_id', $data['vehicle_id']);
        $stmt->bindValue(':filled_at', $filledAt);
        $stmt->bindValue(':odometer_reading', (float)($data['odometer'] ?? 0));
        $stmt->bindValue(':fuel_volume_liters', (float)($data['fuel_volume'] ?? 0));
        
        $price = $data['price_total'] ?? null;
        $stmt->bindValue(':total_cost', $price !== '' ? $price : null);
        $stmt->bindValue(':is_full', $is_full, PDO::PARAM_BOOL);
        $stmt->bindValue(':missed_fill', $missed, PDO::PARAM_BOOL);

        $notes = $data['notes'] ?? '';
        if (!empty($data['station_location'])) {
            $notes .= " [Loc: " . $data['station_location'] . "]";
        }
        $stmt->bindValue(':notes', trim($notes) ?: null);

        $stmt->execute();
        return $stmt->fetch();
    }

    public static function getEfficiencyReport(PDO $pdo, string $vehicleId): array {
        $sql = "
        WITH FinalCalculation AS (
            SELECT
                f.vehicle_id,
                f.filled_at,
                f.true_odometer as current_odometer,
                f.raw_dashboard_odometer,
                f.fuel_volume_liters, 
                (SELECT SUM(s.fuel_volume_liters) FROM v_fuel_log_corrected s WHERE s.vehicle_id = f.vehicle_id AND s.filled_at <= f.filled_at AND s.filled_at > (SELECT COALESCE(MAX(p.filled_at), '1970-01-01') FROM v_fuel_log_corrected p WHERE p.vehicle_id = f.vehicle_id AND p.is_full = true AND p.filled_at < f.filled_at)) as total_fuel_consumed,
                (SELECT BOOL_OR(s.missed_fill) FROM v_fuel_log_corrected s WHERE s.vehicle_id = f.vehicle_id AND s.filled_at <= f.filled_at AND s.filled_at > (SELECT COALESCE(MAX(p.filled_at), '1970-01-01') FROM v_fuel_log_corrected p WHERE p.vehicle_id = f.vehicle_id AND p.is_full = true AND p.filled_at < f.filled_at)) as has_missed_fill,
                f.true_odometer - (SELECT COALESCE(MAX(p.true_odometer), 0) FROM v_fuel_log_corrected p WHERE p.vehicle_id = f.vehicle_id AND p.is_full = true AND p.filled_at < f.filled_at) as distance_traveled
            FROM v_fuel_log_corrected f
            WHERE f.vehicle_id = :vehicle_id AND f.is_full = true 
        )
        SELECT
            filled_at, current_odometer, raw_dashboard_odometer, distance_traveled, total_fuel_consumed, has_missed_fill,
            CASE WHEN has_missed_fill = TRUE THEN NULL WHEN total_fuel_consumed > 0 AND distance_traveled > 0 THEN ROUND((distance_traveled / total_fuel_consumed)::numeric, 2) ELSE 0 END as efficiency_kml
        FROM FinalCalculation
        WHERE distance_traveled > 0
        ORDER BY filled_at DESC;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':vehicle_id' => $vehicleId]);
        return $stmt->fetchAll();
    }

    public static function getLastOdometer(PDO $pdo, string $vehicleId): ?float {
        $stmt = $pdo->prepare("
            SELECT true_odometer
            FROM v_fuel_log_corrected
            WHERE vehicle_id = :vehicle_id
            ORDER BY filled_at DESC
            LIMIT 1
        ");
        $stmt->execute([':vehicle_id' => $vehicleId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : null;
    }
}