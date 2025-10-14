<?php
$title = 'Manage Employees';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$action = get('action');
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $id = (int)post('id');
    $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "employee"')->execute([$id]);
    flash_set('success', 'Employee deleted.');
    redirect('admin/employees.php');
}

$employees = pdo_fetch_all($pdo, 'SELECT * FROM users WHERE role = "employee" ORDER BY created_at DESC');
include __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Employees</h1>
  <a href="<?= base_url('admin/employee_create.php') ?>" class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition">New Employee</a>
</div>


<div class="bg-white border border-slate-200 rounded">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-100 text-slate-600">
          <th class="text-left p-3">Name</th>
          <th class="text-left p-3">Email</th>
          <th class="text-left p-3">Interview date</th>
          <th class="text-left p-3">Completed</th>
          <th class="text-left p-3">Avg Score</th>
          <th class="text-left p-3">Active</th>
          <th class="text-right p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $u): ?>
          <?php
            $metrics = pdo_fetch_one(
              $pdo,
              'SELECT COUNT(*) AS completed, ROUND(AVG(at.percent), 2) AS avg_percent
               FROM attempts at
               JOIN assignments a ON a.id = at.assignment_id
               WHERE a.employee_id = ? AND at.submitted_at IS NOT NULL',
              [$u['id']]
            ) ?: ['completed' => 0, 'avg_percent' => null];
          ?>
          <tr class="border-t">
            <td class="p-3 font-medium text-slate-800"><?= e($u['name']) ?></td>
            <td class="p-3"><?= e($u['email']) ?></td>
            <td class="p-3 text-slate-600"><?= e($u['interview_date']) ?: '—' ?></td>
            <td class="p-3 text-slate-800"><?= (int)($metrics['completed'] ?? 0) ?></td>
            <td class="p-3 text-slate-800"><?= $metrics['avg_percent'] !== null ? ((float)$metrics['avg_percent'] . '%') : '—' ?></td>
            <td class="p-3">
              <span class="px-2 py-0.5 rounded text-xs <?= $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>"><?= $u['is_active'] ? 'Yes' : 'No' ?></span>
            </td>
            <td class="p-3 text-right">
              <a class="text-slate-700 hover:underline" href="<?= base_url('admin/employee_edit.php?id=' . $u['id']) ?>">Edit</a>
              <form method="post" action="<?= base_url('admin/employees.php?action=delete') ?>" class="inline" onsubmit="return confirm('Delete this employee?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                <button class="ml-3 text-red-700 hover:underline" type="submit">Delete</button>
              </form>
              <a class="ml-3 text-primary-700 hover:underline" href="<?= base_url('admin/assignments.php?employee_id=' . $u['id']) ?>">Assign Tests</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
