<?php
$title = 'My Tests';
require_once __DIR__ . '/../includes/functions.php';
require_role('employee');
$pdo = getPDO();

$userId = $_SESSION['user']['id'];
$assignments = pdo_fetch_all($pdo, 'SELECT a.*, t.title, t.time_limit_minutes FROM assignments a JOIN tests t ON t.id = a.test_id WHERE a.employee_id = ? ORDER BY a.assigned_at DESC', [$userId]);

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-semibold mb-4">My Tests</h1>
<div class="bg-white border border-slate-200 rounded">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-100 text-slate-600">
          <th class="text-left p-3">Title</th>
          <th class="text-left p-3">Assigned</th>
          <th class="text-left p-3">Due</th>
          <th class="text-left p-3">Time</th>
          <th class="text-left p-3">Status</th>
          <th class="text-right p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $a): ?>
          <tr class="border-t">
            <td class="p-3 font-medium text-slate-800"><?= e($a['title']) ?></td>
            <td class="p-3 text-slate-600"><?= e($a['assigned_at']) ?></td>
            <td class="p-3 text-slate-600"><?= e($a['due_date']) ?: '—' ?></td>
            <td class="p-3"><?= e($a['time_limit_minutes']) ?: '—' ?> min</td>
            <td class="p-3 capitalize"><?= e($a['status']) ?></td>
            <td class="p-3 text-right">
              <?php if ($a['status'] !== 'completed'): ?>
                <a class="text-primary-700 hover:underline" href="<?= base_url('employee/take.php?assignment_id=' . $a['id']) ?>">Open</a>
              <?php else: ?>
                <span class="text-slate-400">Completed</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
