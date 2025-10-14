<?php
$title = 'Dashboard';
require_once __DIR__ . '/includes/functions.php';
require_login();

$pdo = getPDO();

if (is_admin()) {
    $stats = [
        'employees' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM users WHERE role = "employee"')['c'] ?? 0,
        'tests' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM tests')['c'] ?? 0,
        'assignments' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM assignments')['c'] ?? 0,
        'completed' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM attempts WHERE submitted_at IS NOT NULL')['c'] ?? 0,
    ];
} else {
    $userId = $_SESSION['user']['id'];
    $stats = [
        'assigned' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM assignments WHERE employee_id = ? AND status IN ("assigned","in_progress")', [$userId])['c'] ?? 0,
        'completed' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM assignments a JOIN attempts t ON t.assignment_id = a.id AND t.submitted_at IS NOT NULL WHERE a.employee_id = ?', [$userId])['c'] ?? 0,
    ];
}

include __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <?php if (is_admin()): ?>
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Employees</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['employees'] ?></div>
    </div>
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Tests</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['tests'] ?></div>
    </div>
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Assignments</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['assignments'] ?></div>
    </div>
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Completed Attempts</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['completed'] ?></div>
    </div>
  <?php else: ?>
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Assigned</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['assigned'] ?></div>
    </div>
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Completed</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['completed'] ?></div>
    </div>
  <?php endif; ?>
</div>

<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="bg-white border border-slate-200 rounded p-4 lg:col-span-2">
    <h2 class="font-semibold text-slate-800 mb-3">Progress</h2>
    <canvas id="progressChart" height="110"></canvas>
  </div>
  <div class="bg-white border border-slate-200 rounded p-4">
    <h2 class="font-semibold text-slate-800 mb-3">Quick Links</h2>
    <div class="space-y-2">
      <?php if (is_admin()): ?>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('admin/tests.php') ?>">Manage Tests</a>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('admin/employees.php') ?>">Manage Employees</a>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('admin/assignments.php') ?>">Manage Assignments</a>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('admin/reports.php') ?>">Reports</a>
      <?php else: ?>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('employee/assignments.php') ?>">My Tests</a>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('employee/history.php') ?>">My History</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// Prepare monthly attempt counts from DB
if (is_admin()) {
  $rows = pdo_fetch_all($pdo, 'SELECT DATE_FORMAT(submitted_at, "%Y-%m") m, COUNT(*) c FROM attempts WHERE submitted_at IS NOT NULL GROUP BY m ORDER BY m');
} else {
  $uid = $_SESSION['user']['id'];
  $rows = pdo_fetch_all($pdo, 'SELECT DATE_FORMAT(at.submitted_at, "%Y-%m") m, COUNT(*) c FROM attempts at JOIN assignments a ON a.id = at.assignment_id WHERE at.submitted_at IS NOT NULL AND a.employee_id = ? GROUP BY m ORDER BY m', [$uid]);
}
// Fill 12 months window
$months = [];$counts=[];
$start = new DateTime(date('Y-m-01', strtotime('-11 months')));
for ($i=0;$i<12;$i++) { $key = $start->format('Y-m'); $months[] = $key; $counts[$key]=0; $start->modify('+1 month'); }
foreach ($rows as $r) { if (isset($counts[$r['m']])) { $counts[$r['m']] = (int)$r['c']; } }
$labels = array_map(fn($m)=>date('M', strtotime($m.'-01')), array_keys($counts));
$data = array_values($counts);
?>
<script>
  const ctx = document.getElementById('progressChart');
  if (ctx) {
    const data = {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Completed Attempts',
        data: <?= json_encode($data) ?>,
        borderColor: '#06b6d4',
        backgroundColor: 'rgba(6,182,212,0.2)',
        tension: 0.35,
        fill: true
      }]
    };
    new Chart(ctx, { type: 'line', data, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, precision: 0 } } } });
  }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
