<?php
$title = 'Reports';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$kpis = [
  'employees' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM users WHERE role = "employee"')['c'] ?? 0,
  'tests' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM tests')['c'] ?? 0,
  'assignments' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM assignments')['c'] ?? 0,
  'attempts' => pdo_fetch_one($pdo, 'SELECT COUNT(*) as c FROM attempts')['c'] ?? 0,
];

$recent = pdo_fetch_all($pdo, 'SELECT u.name, t.title, at.percent, at.score_decimal, at.total_points, at.submitted_at FROM attempts at JOIN assignments a ON a.id = at.assignment_id JOIN users u ON u.id = a.employee_id JOIN tests t ON t.id = a.test_id WHERE at.submitted_at IS NOT NULL ORDER BY at.submitted_at DESC LIMIT 20');

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-semibold mb-4">Reports</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
  <div class="card card-hover bg-white rounded p-4 border border-slate-200 w-full">
    <div class="text-slate-500 text-sm">Employees</div>
    <div class="text-3xl font-semibold text-slate-800"><?= (int)$kpis['employees'] ?></div>
  </div>
  <div class="card card-hover bg-white rounded p-4 border border-slate-200 w-full">
    <div class="text-slate-500 text-sm">Tests</div>
    <div class="text-3xl font-semibold text-slate-800"><?= (int)$kpis['tests'] ?></div>
  </div>
  <div class="card card-hover bg-white rounded p-4 border border-slate-200 w-full">
    <div class="text-slate-500 text-sm">Assignments</div>
    <div class="text-3xl font-semibold text-slate-800"><?= (int)$kpis['assignments'] ?></div>
  </div>
  <div class="card card-hover bg-white rounded p-4 border border-slate-200 w-full">
    <div class="text-slate-500 text-sm">Attempts</div>
    <div class="text-3xl font-semibold text-slate-800"><?= (int)$kpis['attempts'] ?></div>
  </div>
</div>

<div class="bg-white border border-slate-200 rounded p-4">
  <h2 class="font-semibold text-slate-800 mb-3">Recent Results</h2>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-100 text-slate-600">
          <th class="text-left p-3">Employee</th>
          <th class="text-left p-3">Test</th>
          <th class="text-left p-3">Submitted</th>
          <th class="text-left p-3">Score</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr class="border-t">
            <td class="p-3 font-medium text-slate-800"><?= e($r['name']) ?></td>
            <td class="p-3"><?= e($r['title']) ?></td>
            <td class="p-3 text-slate-600"><?= e($r['submitted_at']) ?></td>
            <td class="p-3">
              <span class="px-2 py-0.5 rounded bg-sky-100 text-sky-800 text-xs font-medium"><?= (int)$r['score_decimal'] ?>/<?= (int)$r['total_points'] ?> pts • <?= (float)$r['percent'] ?>%</span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
