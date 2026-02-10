<?php
if (!defined('APP_ROOT')) {
  define('APP_ROOT', dirname(__DIR__, 2)); // /views/partials -> / (app root)
}

require_once APP_ROOT . '/models/VehicleModel.php';

$vehicles = VehicleModel::getAll($pdo);
?>

<div class="animate-fade-in-down">
  <div class="flex justify-between items-end mb-4 border-b border-gray-700 pb-2">
    <h2 class="text-xl text-moto-accent digital-font">New Machine</h2>
    <button hx-get="/dashboard" hx-target="#main-content" class="text-xs text-red-400 hover:underline">CANCEL</button>
  </div>

  <form hx-post="/api/vehicle/create" hx-target="#main-content" hx-swap="innerHTML" class="space-y-4">
    <div>
      <label class="block text-xs uppercase text-moto-dim mb-1">Nickname</label>
      <input type="text" name="name" placeholder="e.g. The Cafe Racer" required
        class="w-full bg-moto-panel border border-gray-600 p-3 rounded text-white focus:border-moto-accent focus:outline-none focus:ring-1 focus:ring-moto-accent">
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs uppercase text-moto-dim mb-1">Make</label>
        <input type="text" name="make" placeholder="Honda" required
          class="w-full bg-moto-panel border border-gray-600 p-3 rounded text-white focus:border-moto-accent focus:outline-none">
      </div>
      <div>
        <label class="block text-xs uppercase text-moto-dim mb-1">Year</label>
        <input type="number" name="year" placeholder="1975" required
          class="w-full bg-moto-panel border border-gray-600 p-3 rounded text-white focus:border-moto-accent focus:outline-none">
      </div>
    </div>

    <div>
      <label class="block text-xs uppercase text-moto-dim mb-1">Model</label>
      <input type="text" name="model" placeholder="CB750" required
        class="w-full bg-moto-panel border border-gray-600 p-3 rounded text-white focus:border-moto-accent focus:outline-none">
    </div>

    <div>
      <label class="block text-xs uppercase text-moto-dim mb-1">Initial Odometer</label>
      <input type="number" name="initial_odometer" value="0"
        class="w-full bg-moto-panel border border-gray-600 p-3 rounded text-moto-accent font-mono text-lg focus:border-moto-accent focus:outline-none">
    </div>

    <button type="submit"
      class="w-full bg-moto-accent text-black font-bold py-3 rounded shadow hover:bg-yellow-400 transition uppercase tracking-wider digital-font">
      Park in Garage
    </button>
  </form>
</div>
