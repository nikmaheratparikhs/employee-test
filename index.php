<?php
$title = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
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
        'upcoming' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM assignments WHERE employee_id = ? AND due_date IS NOT NULL AND due_date > NOW()', [$userId])['c'] ?? 0,
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
    <div class="card card-hover bg-white rounded p-4 border border-slate-200">
      <div class="text-slate-500 text-sm">Upcoming Due</div>
      <div class="text-3xl font-semibold text-slate-800"><?= (int)$stats['upcoming'] ?></div>
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
      <?php else: ?>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('employee/assignments.php') ?>">My Tests</a>
        <a class="block px-3 py-2 rounded border hover:bg-slate-50" href="<?= base_url('employee/history.php') ?>">My History</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  const ctx = document.getElementById('progressChart');
  if (ctx) {
    const data = {
      labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
      datasets: [{
        label: 'Completed Attempts',
        data: [3,4,5,6,4,7,10,8,9,12,11,14],
        borderColor: '#06b6d4',
        backgroundColor: 'rgba(6,182,212,0.2)',
        tension: 0.35,
        fill: true
      }]
    };
    new Chart(ctx, { type: 'line', data, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
  }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
