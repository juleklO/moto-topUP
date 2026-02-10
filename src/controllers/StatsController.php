<?php
class StatsController {
  public static function getFuelHistory(PDO $pdo, int $vehicleId): array {
    $stmt = $pdo->prepare("
      SELECT log_date,
             (distance_traveled / fuel_volume) AS mpg
      FROM view_fuel_efficiency
      WHERE vehicle_id = :vid
        AND distance_traveled > 0
      ORDER BY log_date ASC
      LIMIT 10
    ");
    $stmt->execute([':vid' => $vehicleId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
      'labels' => array_map(fn($r) => date('M j', strtotime($r['log_date'])), $rows),
      'data'   => array_map(fn($r) => round((float)$r['mpg'], 1), $rows),
    ];
  }
}
