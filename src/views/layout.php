<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moto TopUP</title>

  <link href="/css/style.css?v=<?= filemtime(APP_ROOT . '/public/css/style.css') ?>" rel="stylesheet">

  <script src="https://unpkg.com/htmx.org@1.9.10"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-moto-dark min-h-screen selection:bg-moto-accent selection:text-black">

  <nav class="border-b border-gray-700 bg-moto-panel p-4 sticky top-0 z-50 shadow-lg">
    <div class="max-w-md mx-auto flex justify-between items-center">
      <h1 class="text-2xl text-moto-accent font-bold italic digital-font">
        MOTO<span class="text-white">LOG</span>
      </h1>

      <button
        hx-get="/api/vehicle/create"
        hx-target="#main-content"
        hx-swap="innerHTML"
        class="text-xs border border-moto-dim text-moto-dim px-3 py-1 rounded hover:border-moto-accent hover:text-moto-accent transition"
      >
        + ADD RIDE
      </button>
    </div>
  </nav>

  <!-- Optional: listen to HX-Trigger refreshVehicleList events -->
  <div
    hx-get="/dashboard"
    hx-trigger="refreshVehicleList from:body"
    hx-target="#main-content"
    hx-swap="innerHTML"
    class="hidden"
  ></div>

  <main id="main-content" class="p-4 max-w-md mx-auto space-y-6">
    <?php require __DIR__ . '/partials/dashboard_fragment.php'; ?>
  </main>

  <script>
    function vehicleStats(vehicleId) {
      return {
        loading: true,
        hasData: false,
        chart: null,

        init() {
          fetch(`/api/stats?id=${vehicleId}`)
            .then(res => res.json())
            .then(stats => {
              this.loading = false;
              this.hasData = Array.isArray(stats.labels) && stats.labels.length > 0;

              if (this.hasData) {
                this.renderChart(stats);
              }
            })
            .catch(() => {
              this.loading = false;
              this.hasData = false;
            });
        },

        renderChart(stats) {
          if (this.chart) this.chart.destroy();

          this.chart = new Chart(this.$refs.canvas, {
            type: 'line',
            data: {
              labels: stats.labels,
              datasets: [{
                label: 'Economy',
                data: stats.data,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 2
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display: false } },
              scales: {
                y: {
                  grid: { color: '#334155' },
                  ticks: { color: '#94a3b8', font: { family: 'Share Tech Mono' } }
                },
                x: {
                  grid: { display: false },
                  ticks: { color: '#94a3b8', font: { family: 'Share Tech Mono' } }
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
