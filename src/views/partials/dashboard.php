<?php
require_once __DIR__ . '/../../models/VehicleModel.php';
require_once __DIR__ . '/../../models/LogModel.php';

$vehicles = VehicleModel::getAll($pdo);

if (count($vehicles) === 0) {
?>
  <div class="bg-gray-900 border border-gray-700 p-4 rounded">
    <h2 class="text-lg mb-2">No vehicles yet</h2>
    <p class="text-sm text-gray-400 mb-4">Create a vehicle via the JSON API first:</p>
    <pre class="text-xs bg-black p-3 rounded border border-gray-700 overflow-auto">POST /api/vehicles</pre>
  </div>
<?php
  return;
}

$vehicle = $vehicles[0];
$logs = LogModel::listEfficiency($pdo, (int)$vehicle['id']);
$logs = array_slice($logs, 0, 10);
?>

<div class="bg-gray-900 border border-gray-700 p-4 rounded mb-4">
  <h2 class="text-lg mb-1"><?php echo htmlspecialchars($vehicle['name']); ?></h2>
  <p class="text-xs text-gray-400">
    <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'); ?>
  </p>
</div>

<div class="space-y-3">
  <h3 class="text-sm uppercase tracking-widest text-gray-400">Recent Fuel Logs</h3>

  <?php if (count($logs) === 0): ?>
    <div class="bg-black border border-gray-700 p-4 rounded text-sm text-gray-400">
      No logs yet. Click <span class="text-yellow-500 font-bold">+ Log Fuel</span> to add the first entry.
    </div>
  <?php endif; ?>

  <?php foreach ($logs as $row): ?>
    <?php
      $distance = $row['distance_traveled'];
      $fuel = $row['fuel_volume'];
      $economy = (is_numeric($distance) && is_numeric($fuel) && (float)$fuel > 0)
        ? ((float)$distance / (float)$fuel)
        : null;
    ?>
    <div class="bg-black border border-gray-700 p-3 rounded">
      <div class="flex justify-between items-baseline">
        <div class="text-sm">
          Odo: <span class="text-green-400"><?php echo (int)$row['odometer']; ?></span>
        </div>
        <div class="text-xs text-gray-500">
          <?php echo htmlspecialchars($row['log_date']); ?>
        </div>
      </div>

      <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
        <div>
          <span class="text-gray-400">Fuel</span><br>
          <span class="text-moto-chrome"><?php echo htmlspecialchars($fuel); ?></span>
        </div>
        <div>
          <span class="text-gray-400">Dist</span><br>
          <span class="text-moto-chrome"><?php echo ($distance === null) ? '—' : htmlspecialchars($distance); ?></span>
        </div>
        <div>
          <span class="text-gray-400">Eco</span><br>
          <span class="text-yellow-500 font-bold">
            <?php echo ($economy === null) ? '—' : number_format($economy, 2); ?>
          </span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>