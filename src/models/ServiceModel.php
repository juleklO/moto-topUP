<?php
// src/models/ServiceModel.php

class ServiceModel {
    
    /**
     * Fetch available service tasks (e.g. "Oil Change", "Tires")
     */
    public static function getTasks(PDO $pdo): array {
        return $pdo->query("SELECT * FROM service_tasks ORDER BY name")->fetchAll();
    }

    /**
     * Add a service entry AND update the schedule if applicable.
     */
    public static function add(PDO $pdo, array $data): bool {
        try {
            $pdo->beginTransaction();

            // 1. Insert the Service Entry
            $stmt = $pdo->prepare("
                INSERT INTO service_entries (
                    vehicle_id, service_task_id, performed_at, 
                    odometer_at_service, cost, meta
                ) VALUES (
                    :vehicle_id, :task_id, :performed_at, 
                    :odo, :cost, :meta
                )
            ");
            
            $meta = json_encode(['notes' => $data['notes'] ?? '']);
            
            $stmt->execute([
                ':vehicle_id'   => $data['vehicle_id'],
                ':task_id'      => $data['service_task_id'] ?: null,
                ':performed_at' => $data['performed_at'],
                ':odo'          => $data['odometer'],
                ':cost'         => $data['cost'] ?: null,
                ':meta'         => $meta
            ]);

            // 2. Update Maintenance Schedule (The Reset Logic)
            // If this service corresponds to a scheduled task, reset its counter.
            if (!empty($data['service_task_id'])) {
                $upd = $pdo->prepare("
                    UPDATE maintenance_schedules 
                    SET last_performed_at = :performed_at,
                        last_performed_odometer = :odo,
                        updated_at = NOW()
                    WHERE vehicle_id = :vehicle_id 
                      AND service_task_id = :task_id
                ");
                
                $upd->execute([
                    ':performed_at' => $data['performed_at'],
                    ':odo'          => $data['odometer'],
                    ':vehicle_id'   => $data['vehicle_id'],
                    ':task_id'      => $data['service_task_id']
                ]);
            }

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Service Add Error: " . $e->getMessage());
            return false;
        }
    }
}