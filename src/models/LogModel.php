<?php
class LogModel {
    public static function add(PDO $pdo, array $data): array {
        // Use array_key_exists so false is not “lost”
        $is_full = array_key_exists('is_full', $data) ? (bool)$data['is_full'] : true;
        $missed  = array_key_exists('missed_previous', $data) ? (bool)$data['missed_previous'] : false;

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

        // Bind with correct types (critical for Postgres booleans)
        $stmt->bindValue(':vehicle_id', (int)($data['vehicle_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':odometer', (int)($data['odometer'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':fuel_volume', (float)($data['fuel_volume'] ?? 0));

        // Optional fields
        $price_total = $data['price_total'] ?? null;
        $fuel_grade  = $data['fuel_grade'] ?? null;
        $station     = $data['station_location'] ?? null;
        $notes       = $data['notes'] ?? null;

        if ($price_total === null) $stmt->bindValue(':price_total', null, PDO::PARAM_NULL);
        else $stmt->bindValue(':price_total', $price_total);

        if ($fuel_grade === null) $stmt->bindValue(':fuel_grade', null, PDO::PARAM_NULL);
        else $stmt->bindValue(':fuel_grade', $fuel_grade);

        // These are the ones that were breaking:
        $stmt->bindValue(':is_full', $is_full, PDO::PARAM_BOOL);
        $stmt->bindValue(':missed_previous', $missed, PDO::PARAM_BOOL);

        if ($station === null) $stmt->bindValue(':station_location', null, PDO::PARAM_NULL);
        else $stmt->bindValue(':station_location', $station);

        if ($notes === null) $stmt->bindValue(':notes', null, PDO::PARAM_NULL);
        else $stmt->bindValue(':notes', $notes);

        $stmt->execute();
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