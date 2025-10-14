<?php
require_once __DIR__ . '/../includes/auth.php';
$config = require __DIR__ . '/../config/config.php';
$flash = flash_get_all();
$title = $title ?? $config['app_name'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#ecfeff', 100: '#cffafe', 200: '#a5f3fc', 300: '#67e8f9', 400: '#22d3ee', 500: '#06b6d4', 600: '#0891b2', 700: '#0e7490', 800: '#155e75', 900: '#164e63'
            }
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>" />
  <script src="<?= base_url('assets/js/dashboard.js') ?>" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen flex">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 flex flex-col ml-0 md:ml-64">
      <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200">
        <div class="px-4 py-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <button id="sidebarToggle" class="md:hidden inline-flex items-center p-2 rounded hover:bg-slate-100 focus:outline-none" aria-label="Toggle sidebar">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
            <span class="font-semibold text-primary-700"><?= htmlspecialchars($config['app_name']) ?></span>
          </div>
          <div class="flex items-center gap-3">
            <?php if (is_logged_in()): ?>
              <span class="text-sm text-slate-500 hidden sm:inline">Hi, <?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?></span>
              <a href="<?= base_url('logout.php') ?>" class="text-sm px-3 py-1.5 rounded bg-primary-600 text-white hover:bg-primary-700 transition">Logout</a>
            <?php else: ?>
              <a href="<?= base_url('login.php') ?>" class="text-sm px-3 py-1.5 rounded bg-primary-600 text-white hover:bg-primary-700 transition">Login</a>
            <?php endif; ?>
          </div>
        </div>
      </header>
      <div class="flex-1 p-4 w-full">
        <?php if (!empty($flash)): ?>
          <div class="space-y-2 mb-4">
            <?php foreach ($flash as $type => $messages): ?>
              <?php foreach ($messages as $message): ?>
                <div class="px-4 py-3 rounded border shadow-sm <?php echo $type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800'; ?>">
                  <?= htmlspecialchars($message) ?>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
