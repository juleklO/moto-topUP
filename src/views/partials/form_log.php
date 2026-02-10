<?php
require_once __DIR__ . '/../../models/VehicleModel.php';
require_once __DIR__ . '/../../models/LogModel.php';

$vehicles = VehicleModel::getAll($pdo);
if (count($vehicles) === 0) {
?>
  <div class="bg-gray-900 border border-gray-700 p-4 rounded">
    <p class="text-sm text-gray-400">No vehicles exist yet. Create one via <code>POST /api/vehicles</code>.</p>
  </div>
<?php
  return;
}

$vehicle = $vehicles[0];
$vehicle_id = (int)$vehicle['id'];
$previous = LogModel::getLastOdometer($pdo, $vehicle_id);
$previous = $previous ?? (int)($vehicle['initial_odometer'] ?? 0);
?>

<form
  hx-post="/api/submit-log"
  hx-target="#main-content"
  class="bg-gray-800 p-6 rounded shadow-lg border border-gray-600"
>
  <h2 class="text-lg mb-4 border-b border-gray-600 pb-2">NEW ENTRY</h2>

  <!-- Vehicle -->
  <div class="mb-4">
    <label class="block text-xs uppercase text-gray-400">Vehicle</label>
    <select name="vehicle_id" class="w-full bg-black border border-gray-600 p-2 text-green-400 focus:outline-none focus:border-yellow-500">
      <?php foreach ($vehicles as $v): ?>
        <option value="<?php echo (int)$v['id']; ?>">
          <?php echo htmlspecialchars($v['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Trip calculation uses last odometer of the first vehicle for now.</p>
  </div>

  <!-- Odometer -->
  <div x-data="{ current: 0, previous: <?php echo (int)$previous; ?> }" class="mb-4">
    <label class="block text-xs uppercase text-gray-400">Odometer</label>
    <input
      type="number"
      name="odometer"
      x-model.number="current"
      class="w-full bg-black border border-gray-600 p-2 text-green-400 focus:outline-none focus:border-yellow-500"
      required
      min="0"
    >
    <p class="text-xs text-gray-500 mt-1">
      Trip:
      <span
        x-text="(current >= previous) ? (current - previous) + ' km' : '---'"
        class="text-yellow-500"
      ></span>
      <span class="text-gray-600">(prev: <?php echo (int)$previous; ?>)</span>
    </p>
  </div>

  <!-- Liters -->
  <div class="mb-4">
    <label class="block text-xs uppercase text-gray-400">Liters</label>
    <input
      type="number"
      step="0.01"
      name="liters"
      class="w-full bg-black border border-gray-600 p-2 text-green-400 focus:outline-none focus:border-yellow-500"
      required
      min="0.01"
    >
  </div>

  <!-- Flags -->
  <div class="mb-4 flex gap-4 text-xs text-gray-300">
    <label class="flex items-center gap-2">
      <input type="checkbox" name="is_full" checked>
      Full tank
    </label>
    <label class="flex items-center gap-2">
      <input type="checkbox" name="missed_previous">
      Missed previous
    </label>
  </div>

  <div class="flex gap-2 mt-6">
    <button type="submit" class="flex-1 bg-green-700 hover:bg-green-600 text-white py-2 uppercase text-sm">
      Save
    </button>

    <button
      type="button"
      hx-get="/"
      hx-target="#main-content"
      class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 uppercase text-sm"
    >
      Cancel
    </button>
  </div>
</form>