<?php
// src/views/partials/dashboard.php

require_once __DIR__ . '/../../models/VehicleModel.php';
require_once __DIR__ . '/../../models/LogModel.php';

// Fetch Vehicles
$vehicles = VehicleModel::getAll($pdo);

if (empty($vehicles)) {
?>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-6 rounded-lg text-center shadow-sm">
    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Welcome to MotoLog</h2>
    <p class="text-slate-500 dark:text-slate-400 mb-4">Start by adding your first machine.</p>
    <button hx-get="/vehicle/create" hx-target="#main-content" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-500 font-medium transition-colors">
        Add Vehicle
    </button>
  </div>
<?php
  return;
}

$vehicle = $vehicles[0]; 
$vehicleId = $vehicle['id'];

// Get efficiency report (last 10 cycles)
$logs = LogModel::getEfficiencyReport($pdo, $vehicleId);
$logs = array_slice($logs, 0, 10);

$units = $vehicle['system_of_measurement'] ?? 'metric';
$distLabel = ($units === 'metric') ? 'km' : 'mi';
$volLabel  = ($units === 'metric') ? 'L' : 'gal';
$effLabel  = ($units === 'metric') ? 'km/L' : 'mpg';

$kmToMile = 0.621371;
$literToGalUS = 0.264172;
$literToGalUK = 0.219969;

// Fetch Maintenance Status
$currentOdo = LogModel::getLastOdometer($pdo, $vehicleId) ?? 0;
require_once __DIR__ . '/../../models/MaintenanceModel.php';
$maintReport = MaintenanceModel::getStatusReport($pdo, $vehicleId, $currentOdo);
$usage = $maintReport['usage_stats'];
?>

<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 rounded-lg mb-6 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
  <div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($vehicle['name']); ?></h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
      <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>
    </p>
  </div>
  <div class="flex gap-2 w-full sm:w-auto">
      <button 
        hx-get="/service/create?vehicle_id=<?= $vehicleId ?>" 
        hx-target="#main-content" 
        class="flex-1 sm:flex-none bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 rounded shadow transition-colors text-center">
        Repair
      </button>
      
      <button 
        hx-get="/log/create?vehicle_id=<?= $vehicleId ?>" 
        hx-target="#main-content" 
        class="flex-1 sm:flex-none bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded shadow transition-colors text-center">
        + Refuel
      </button>
  </div>
</div>

<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 rounded-lg mb-6 shadow-sm h-64"
     x-data="vehicleStats('<?= $vehicleId ?>')"
     x-init="init()">
    <canvas x-ref="canvas"></canvas>
    <div x-show="loading" class="text-center text-slate-500 mt-10">Loading chart...</div>
    <div x-show="!loading && !hasData" class="text-center text-slate-500 mt-10 font-medium">Not enough full fill-ups for graph</div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 rounded-lg shadow-sm">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Fleet Intelligence</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Est. Daily Usage</p>
                <p class="text-2xl font-mono font-bold text-slate-900 dark:text-white">
                    <?= number_format($usage['predicted_km_per_day'], 1) ?> 
                    <span class="text-sm font-sans text-slate-500 font-normal">km/day</span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-1">Prediction Model</p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold 
                    <?= ($usage['prediction_method'] === 'linear_regression') ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' ?>">
                    <?= ($usage['prediction_method'] === 'linear_regression') ? 'Linear Regression' : 'Simple Avg' ?>
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 rounded-lg shadow-sm">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Service Forecast</h3>
        <?php if (empty($maintReport['schedules'])): ?>
            <p class="text-slate-500 text-sm italic">No active maintenance schedules.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($maintReport['schedules'] as $task): ?>
                    <?php 
                        $colorClass = match($task['status']) {
                            'overdue'  => 'text-red-700 border-red-200 bg-red-50 dark:text-red-400 dark:border-red-900/50 dark:bg-red-900/20',
                            'due_soon' => 'text-orange-700 border-orange-200 bg-orange-50 dark:text-orange-400 dark:border-orange-900/50 dark:bg-orange-900/20',
                            default    => 'text-green-700 border-green-200 bg-green-50 dark:text-green-400 dark:border-green-900/50 dark:bg-green-900/20'
                        };
                    ?>
                    <div class="flex justify-between items-center p-3 rounded-md border <?= $colorClass ?>">
                        <span class="font-bold"><?= htmlspecialchars($task['task_name']) ?></span>
                        <div class="text-right">
                            <span class="block text-sm font-bold">
                                <?= ($task['days_remaining_predicted'] < 0) ? 'OVERDUE' : $task['days_remaining_predicted'] . ' Days' ?>
                            </span>
                            <span class="text-xs opacity-80 font-medium">
                                <?= number_format($task['dist_remaining_km']) ?> km left
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="space-y-3">
  <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Efficiency History</h3>

  <?php if (empty($logs)): ?>
    <div class="p-4 text-slate-500 dark:text-slate-400 text-center text-sm bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg">
      No full efficiency cycles calculated yet.
    </div>
  <?php endif; ?>

  <?php foreach ($logs as $row): ?>
    <?php
      $dist = $row['distance_traveled'];
      $fuel = $row['total_fuel_consumed'];
      $eff  = $row['efficiency_kml'];
      $galFactor = ($units === 'imperial_uk') ? $literToGalUK : $literToGalUS;

      if ($units !== 'metric') {
          $dist = $dist * $kmToMile;
          $fuel = $fuel * $galFactor;
          if ($eff !== null && $fuel > 0) $eff = $dist / $fuel;
      }
    ?>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-lg shadow-sm">
      <div class="flex justify-between items-baseline mb-3">
        <div class="text-base font-mono font-bold text-indigo-600 dark:text-indigo-400">
          <?php if ($eff === null): ?>
             <span class="text-slate-400">N/A</span>
          <?php else: ?>
             <?php echo number_format($eff, 1); ?> <span class="text-xs font-sans text-slate-500"><?= $effLabel ?></span>
          <?php endif; ?>
        </div>
        <div class="text-xs font-medium text-slate-500">
          <?php echo date('M j, Y', strtotime($row['filled_at'])); ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>