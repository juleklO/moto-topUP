<?php
// src/views/partials/form_log.php

// Expects: $vehicle (array) to determine units
$unitSystem = $vehicle['system_of_measurement'] ?? 'metric';
$distUnit = ($unitSystem === 'metric') ? 'km' : 'mi';
$volUnit  = ($unitSystem === 'metric') ? 'L' : 'gal';

// Current time for default value
$nowValue = date('Y-m-d\TH:i');
?>

<div class="bg-white dark:bg-slate-800 shadow-md rounded-lg p-6 mb-6 border border-slate-200 dark:border-slate-700" 
     x-data="{ 
         advanced: false,
         isFull: true
     }">
     
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            Log Refuel
        </h2>
        
        <button type="button" @click="advanced = !advanced" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
            <span x-text="advanced ? 'Simple Mode' : 'Advanced Mode'">Advanced Mode</span>
        </button>
    </div>

    <form action="/log/add" method="POST" class="space-y-4">
        <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vehicle['id']) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Date & Time
                </label>
                <input type="datetime-local" name="filled_at" value="<?= $nowValue ?>" required
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Odometer (<?= $distUnit ?>)
                </label>
                <div class="relative">
                    <input type="number" step="0.1" name="odometer" required
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg"
                           placeholder="e.g. 12500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Volume (<?= $volUnit ?>)
                </label>
                <input type="number" step="0.001" name="fuel_volume" required
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg"
                       placeholder="e.g. 12.5">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Total Cost</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <span class="text-slate-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" step="0.01" name="price_total"
                           class="block w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white pl-7 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           placeholder="0.00">
                </div>
            </div>

            <div class="flex items-center h-full pt-6">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_full" x-model="isFull" class="sr-only peer" checked>
                    <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    <span class="ms-3 text-sm font-medium text-slate-900 dark:text-slate-300">
                        Full Tank?
                    </span>
                </label>
            </div>
        </div>

        <div x-show="!isFull" x-transition 
             class="p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-md text-sm text-yellow-800 dark:text-yellow-200">
            <strong>Note:</strong> Efficiency won't be calculated for this entry until the next full tank.
        </div>

        <div x-show="advanced" x-transition class="space-y-4 pt-2 border-t border-slate-100 dark:border-slate-700">
            
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="missed_fill" name="missed_fill" type="checkbox" 
                           class="w-4 h-4 border border-slate-300 rounded bg-slate-50 focus:ring-3 focus:ring-indigo-300 dark:bg-slate-700 dark:border-slate-600 dark:focus:ring-indigo-600 dark:ring-offset-slate-800">
                </div>
                <label for="missed_fill" class="ms-2 text-sm font-medium text-slate-900 dark:text-slate-300">
                    Missed a previous log? 
                    <span class="block text-xs text-slate-500 dark:text-slate-400 font-normal">Check this if you forgot to log a fill-up since the last entry. This prevents efficiency calculation errors.</span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Station / Location</label>
                <input type="text" name="station_location" 
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                <textarea name="notes" rows="2" 
                          class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            </div>
        </div>

        <button type="submit" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Save Log Entry
        </button>
    </form>
</div>