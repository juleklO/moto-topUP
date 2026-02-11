<!DOCTYPE html>
<html lang="en" x-data="themeStore()" :class="{ 'dark': isDark }">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moto TopUP</title>

  <link href="/css/style.css?v=<?= filemtime(APP_ROOT . '/public/css/style.css') ?>" rel="stylesheet">

  <script src="https://unpkg.com/htmx.org@1.9.10"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen selection:bg-indigo-500 selection:text-white transition-colors duration-200">

  <nav class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sticky top-0 z-50 shadow-sm">
    <div class="max-w-3xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold tracking-tight">
        <a href="/" class="text-indigo-600 dark:text-indigo-400">MOTO<span class="text-slate-800 dark:text-white">LOG</span></a>
      </h1>

      <div class="flex items-center gap-4">
        <button @click="toggle()" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition">
          <svg x-show="isDark" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
          <svg x-show="!isDark" class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
        </button>

        <button
          hx-get="/vehicle/create"
          hx-target="#main-content"
          hx-swap="innerHTML"
          class="text-xs border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 px-3 py-1.5 rounded hover:border-indigo-500 hover:text-indigo-600 dark:hover:border-indigo-400 dark:hover:text-indigo-400 transition"
        >
          + ADD RIDE
        </button>
      </div>
    </div>
  </nav>

  <div
    hx-get="/dashboard"
    hx-trigger="refreshVehicleList from:body"
    hx-target="#main-content"
    hx-swap="innerHTML"
    class="hidden"
  ></div>

  <main id="main-content" class="p-4 max-w-3xl mx-auto space-y-6">
    <?php require __DIR__ . '/partials/dashboard_fragment.php'; ?>
  </main>

  <script>
    // Section 6.2.1: Client-Side State Management (Dark Mode)
    function themeStore() {
        return {
            isDark: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
            toggle() {
                this.isDark = !this.isDark;
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            }
        }
    }

    function vehicleStats(vehicleId) {
      return {
        loading: true,
        hasData: false,
        chart: null,

        init() {
          fetch(`/api/stats?vehicle_id=${vehicleId}`)
            .then(res => res.json())
            .then(statsData => {
              this.loading = false;
              
              // Filter out NULL efficiency values (missed logs) and map for Chart.js
              const validLogs = statsData.filter(log => log.efficiency_kml !== null && log.efficiency_kml > 0).reverse();
              this.hasData = validLogs.length > 0;

              if (this.hasData) {
                const labels = validLogs.map(log => new Date(log.filled_at).toLocaleDateString(undefined, {month: 'short', day: 'numeric'}));
                const dataPoints = validLogs.map(log => log.efficiency_kml);
                this.renderChart(labels, dataPoints);
              }
            })
            .catch(err => {
              console.error(err);
              this.loading = false;
              this.hasData = false;
            });
        },

        renderChart(labels, data) {
          if (this.chart) this.chart.destroy();

          const isDark = document.documentElement.classList.contains('dark');
          const gridColor = isDark ? '#334155' : '#e2e8f0';
          const textColor = isDark ? '#94a3b8' : '#64748b';

          this.chart = new Chart(this.$refs.canvas, {
            type: 'line',
            data: {
              labels: labels,
              datasets: [{
                label: 'Efficiency',
                data: data,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#6366f1'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display: false } },
              scales: {
                y: {
                  grid: { color: gridColor },
                  ticks: { color: textColor }
                },
                x: {
                  grid: { display: false },
                  ticks: { color: textColor }
                }
              }
            }
          });
        }
      }
    }
  </script>
</body>
</html>