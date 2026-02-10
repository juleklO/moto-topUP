<?php
if (!defined('APP_ROOT')) {
  define('APP_ROOT', dirname(__DIR__, 2)); // /views/partials -> / (app root)
}

require_once APP_ROOT . '/models/VehicleModel.php';

$vehicles = VehicleModel::getAll($pdo);
?>

<?php
require_once APP_ROOT . '/models/VehicleModel.php';
require_once APP_ROOT . '/models/LogModel.php';

$vehicles = VehicleModel::getAll($pdo);
if (count($vehicles) === 0) {
  echo '<div class="bg-moto-panel border border-gray-700 p-4 rounded text-sm text-moto-dim">No vehicles exist yet.</div>';
  return;
}

$selectedVehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : (int)$vehicles[0]['id'];
$selectedVehicleId = $selectedVehicleId > 0 ? $selectedVehicleId : (int)$vehicles[0]['id'];

$previous = LogModel::getLastOdometer($pdo, $selectedVehicleId);
$previous = $previous ?? 0;
?>

<form
  hx-post="/api/submit-log"
  hx-target="#main-content"
  hx-swap="innerHTML"
  class="bg-moto-panel p-6 rounded shadow-lg border border-gray-700 animate-fade-in-down"
>
  <div class="flex justify-between items-end mb-4 border-b border-gray-700 pb-2">
    <h2 class="text-xl text-moto-accent digital-font">Top Up</h2>
    <button type="button" hx-get="/dashboard" hx-target="#main-content" class="text-xs text-red-400 hover:underline">CANCEL</button>
  </div>

  <div class="mb-4">
    <label class="block text-xs uppercase text-moto-dim mb-1">Vehicle</label>
    <select name="vehicle_id" class="w-full bg-black/20 border border-gray-600 p-3 rounded text-white focus:border-moto-accent focus:outline-none">
      <?php foreach ($vehicles as $v): ?>
        <option value="<?= (int)$v['id'] ?>" <?= ((int)$v['id'] === $selectedVehicleId) ? 'selected' : '' ?>>
          <?= htmlspecialchars($v['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div x-data="{ current: 0, previous: <?= (int)$previous ?> }" class="mb-4">
    <label class="block text-xs uppercase text-moto-dim mb-1">Odometer</label>
    <input
      type="number"
      name="odometer"
      x-model.number="current"
      class="w-full bg-black/20 border border-gray-600 p-3 rounded text-moto-accent font-mono text-lg focus:outline-none focus:border-moto-accent"
      required
      min="0"
    >
    <p class="text-xs text-moto-dim mt-1">
      Trip: <span x-text="(current >= previous) ? (current - previous) + ' km' : '---'" class="text-moto-accent"></span>
      <span class="text-gray-500">(prev: <?= (int)$previous ?>)</span>
    </p>
  </div>

  <div class="mb-4">
    <label class="block text-xs uppercase text-moto-dim mb-1">Liters</label>
    <input
      type="number"
      step="0.01"
      name="liters"
      class="w-full bg-black/20 border border-gray-600 p-3 rounded text-white focus:outline-none focus:border-moto-accent"
      required
      min="0.01"
    >
  </div>

  <div class="mb-4 flex gap-4 text-xs text-moto-dim">
    <label class="flex items-center gap-2">
      <input type="checkbox" name="is_full" checked>
      Full tank
    </label>
    <label class="flex items-center gap-2">
      <input type="checkbox" name="missed_previous">
      Missed previous
    </label>
  </div>

  <button type="submit"
    class="w-full bg-moto-accent text-black font-bold py-3 rounded shadow hover:bg-yellow-400 transition uppercase tracking-wider digital-font">
    Save Entry
  </button>
</form>
