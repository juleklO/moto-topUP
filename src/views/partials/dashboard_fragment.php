<?php
if (!defined('APP_ROOT')) {
  define('APP_ROOT', dirname(__DIR__, 2)); // /views/partials -> / (app root)
}

require_once APP_ROOT . '/models/VehicleModel.php';

$vehicles = VehicleModel::getAll($pdo);
?>

<div id="vehicle-list" class="space-y-6">
  <?php if (count($vehicles) === 0): ?>
    <div class="bg-moto-panel rounded-lg shadow-lg border border-gray-700 p-4">
      <h2 class="text-lg text-moto-accent digital-font mb-2">No vehicles yet</h2>
      <p class="text-sm text-moto-dim">Click <span class="text-moto-accent font-bold">+ ADD RIDE</span> to park your first machine.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($vehicles as $bike): ?>
    <div class="bg-moto-panel rounded-lg shadow-lg overflow-hidden border border-gray-700"
         x-data="vehicleStats('<?= htmlspecialchars($bike['id']) ?>')"
         x-init="init()">

      <div class="p-4 bg-black/20 flex justify-between items-center border-b border-gray-700">
        <div>
          <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($bike['name']) ?></h3>
          <p class="text-xs text-moto-dim font-mono">
            <?= htmlspecialchars($bike['year'] . ' ' . $bike['make'] . ' ' . $bike['model']) ?>
          </p>
        </div>

        <button
          hx-get="/api/log/create?vehicle_id=<?= htmlspecialchars($bike['id']) ?>"
          hx-target="#main-content"
          hx-swap="innerHTML"
          class="bg-moto-dim/20 hover:bg-moto-accent hover:text-black text-moto-accent text-xs px-3 py-2 rounded transition digital-font">
          TOP UP
        </button>
      </div>

      <div class="p-4">
        <div x-show="loading" class="text-center text-xs text-moto-dim py-4">
          Loading telemetry...
        </div>

        <div x-show="!loading && !hasData" class="text-center text-xs text-moto-dim py-4">
          No telemetry yet. Add a couple of logs.
        </div>

        <div x-show="!loading && hasData" class="relative h-40 w-full">
          <canvas x-ref="canvas"></canvas>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>