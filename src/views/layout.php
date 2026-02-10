<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moto TopUP</title>

  <!-- Font for vintage digital look -->
  <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">

  <!-- Tailwind output -->
  <link href="/css/style.css" rel="stylesheet">

  <!-- HTMX + Alpine (CDN, no build step) -->
  <script src="https://unpkg.com/htmx.org@1.9.10"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-moto-oil text-moto-chrome font-mono min-h-screen">
  <nav class="border-b border-gray-700 p-4 flex justify-between items-center">
    <h1 class="text-xl tracking-widest uppercase">
      Vintage<span class="text-yellow-500">Log</span>
    </h1>

    <button
      hx-get="/api/add-log-form"
      hx-target="#main-content"
      class="bg-yellow-600 hover:bg-yellow-500 text-black px-4 py-2 rounded-sm uppercase text-sm font-bold">
      + Log Fuel
    </button>
  </nav>

  <main id="main-content" class="p-4 max-w-md mx-auto">
    <?php require __DIR__ . '/partials/dashboard.php'; ?>
  </main>
</body>
</html>