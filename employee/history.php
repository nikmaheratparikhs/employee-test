<?php
$title = 'My History';
require_once __DIR__ . '/../includes/functions.php';
require_role('employee');
$pdo = getPDO();

$userId = $_SESSION['user']['id'];
$attempts = pdo_fetch_all($pdo, 'SELECT t.title, at.submitted_at, at.percent FROM attempts at JOIN assignments a ON a.id = at.assignment_id JOIN tests t ON t.id = a.test_id WHERE a.employee_id = ? AND at.submitted_at IS NOT NULL ORDER BY at.submitted_at DESC', [$userId]);

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-semibold mb-4">My Results</h1>
<div class="bg-white border border-slate-200 rounded">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-100 text-slate-600">
          <th class="text-left p-3">Test</th>
          <th class="text-left p-3">Submitted</th>
          <th class="text-left p-3">Score</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($attempts as $row): ?>
          <tr class="border-t">
            <td class="p-3 font-medium text-slate-800"><?= e($row['title']) ?></td>
            <td class="p-3 text-slate-600"><?= e($row['submitted_at']) ?></td>
            <td class="p-3"><span class="px-2 py-0.5 rounded bg-sky-100 text-sky-800 text-xs font-medium"><?= (float)$row['percent'] ?>%</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
