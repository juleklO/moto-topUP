<?php
// src/views/partials/form_service.php
$nowValue = date('Y-m-d\TH:i');
?>

<div class="bg-white dark:bg-slate-800 shadow-md rounded-lg p-6 mb-6 border border-slate-200 dark:border-slate-700">
    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-4">
        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        Log Service / Repair
    </h2>

    <form action="/service/add" method="POST" class="space-y-4">
        <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vehicle['id']) ?>">

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Service Task</label>
            <select name="service_task_id" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white p-2">
                <option value="">-- General Repair / Other --</option>
                <?php foreach ($tasks as $task): ?>
                    <option value="<?= $task['id'] ?>"><?= htmlspecialchars($task['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-1">Selecting a task will reset its maintenance schedule.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                <input type="datetime-local" name="performed_at" value="<?= $nowValue ?>" required
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Odometer</label>
                <input type="number" step="0.1" name="odometer" required
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cost</label>
            <input type="number" step="0.01" name="cost" placeholder="0.00"
                   class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes / Parts Used</label>
            <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white"></textarea>
        </div>

        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
            Save Service Record
        </button>
    </form>
</div>