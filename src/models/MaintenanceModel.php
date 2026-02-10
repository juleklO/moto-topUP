<?php
// src/models/MaintenanceModel.php

class MaintenanceModel {
    
    public static function getUsageStats(PDO $pdo, string $vehicleId): array {
        // FIXED: Explicitly cast to double precision to satisfy PostgreSQL's strict math engine
        $sql = "
            SELECT
                COUNT(*) as data_points,
                COALESCE(
                    regr_slope(true_odometer::double precision, EXTRACT(EPOCH FROM filled_at)::double precision) * 86400, 
                    0
                ) as predicted_km_per_day,
                regr_r2(true_odometer::double precision, EXTRACT(EPOCH FROM filled_at)::double precision) as fit_confidence
            FROM v_fuel_log_corrected
            WHERE vehicle_id = :vehicle_id
              AND filled_at > NOW() - INTERVAL '180 days'
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':vehicle_id' => $vehicleId]);
        $stats = $stmt->fetch();

        if ($stats['data_points'] < 3 || $stats['fit_confidence'] < 0.5) {
            $stats['prediction_method'] = 'simple_average';
        } else {
            $stats['prediction_method'] = 'linear_regression';
        }

        return $stats;
    }

    public static function getStatusReport(PDO $pdo, string $vehicleId, float $currentOdometer): array {
        $usage = self::getUsageStats($pdo, $vehicleId);
        $kmPerDay = max((float)$usage['predicted_km_per_day'], 0.1); 

        $sql = "
            SELECT 
                ms.id,
                t.name as task_name,
                ms.interval_distance_km,
                ms.last_performed_odometer,
                ms.last_performed_at,
                (ms.last_performed_odometer + ms.interval_distance_km) - :current_odometer as dist_remaining_km
            FROM maintenance_schedules ms
            JOIN service_tasks t ON ms.service_task_id = t.id
            WHERE ms.vehicle_id = :vehicle_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':current_odometer' => $currentOdometer
        ]);
        $schedules = $stmt->fetchAll();

        foreach ($schedules as &$item) {
            $distRemaining = (float)$item['dist_remaining_km'];
            $daysRemaining = ($distRemaining > 0) ? ($distRemaining / $kmPerDay) : 0;
            
            $item['days_remaining_predicted'] = (int)$daysRemaining;
            
            if ($distRemaining <= 0) {
                $item['status'] = 'overdue';
            } elseif ($daysRemaining < 30) {
                $item['status'] = 'due_soon';
            } else {
                $item['status'] = 'ok';
            }
        }

        return [
            'usage_stats' => $usage,
            'schedules' => $schedules
        ];
    }
}