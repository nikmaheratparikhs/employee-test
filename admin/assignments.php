<?php
$title = 'Manage Assignments';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$employeeId = (int)(get('employee_id') ?? 0);
$employees = pdo_fetch_all($pdo, 'SELECT id, name, email FROM users WHERE role = "employee" ORDER BY name ASC');
$tests = pdo_fetch_all($pdo, 'SELECT id, title FROM tests WHERE is_active = 1 ORDER BY title ASC');

$action = get('action');
if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $id = (int)post('id');
    $pdo->prepare('DELETE FROM assignments WHERE id = ?')->execute([$id]);
    flash_set('success', 'Assignment removed.');
    redirect('admin/assignments.php');
}
if ($action === 'reassign' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $id = (int)post('id');
    $due = trim((string)post('due_date'));
    // Increment attempt limit and reset status
    $stmt = $pdo->prepare('UPDATE assignments SET status="assigned", due_date = ?, attempt_limit = attempt_limit + 1 WHERE id = ?');
    $stmt->execute([$due ?: null, $id]);
    flash_set('success', 'Reassigned: status reset and attempt limit increased.');
    redirect('admin/assignments.php');
}

$currentEmp = $employeeId ? pdo_fetch_one($pdo, 'SELECT id, name, email FROM users WHERE id = ? AND role = "employee"', [$employeeId]) : null;
$where = $employeeId ? 'WHERE a.employee_id = ' . (int)$employeeId : '';
$list = $pdo->query('SELECT a.*, u.name as employee_name, t.title as test_title FROM assignments a JOIN users u ON u.id = a.employee_id JOIN tests t ON t.id = a.test_id ' . $where . ' ORDER BY a.assigned_at DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 class="text-xl font-semibold">Assignments</h1>
</div>

<div class="bg-white border border-slate-200 rounded p-4 mb-6">
  <div class="flex items-center justify-between">
    <h2 class="font-semibold">Assign a Test</h2>
    <a href="<?= base_url('admin/assignment_create.php' . ($employeeId ? ('?employee_id=' . $employeeId) : '')) ?>" class="px-3 py-2 rounded bg-primary-600 text-white">New Assignment</a>
  </div>
</div>

<div class="bg-white border border-slate-200 rounded">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-100 text-slate-600">
          <th class="text-left p-3">Employee</th>
          <th class="text-left p-3">Test</th>
          <th class="text-left p-3">Assigned</th>
          <th class="text-left p-3">Due</th>
          <th class="text-left p-3">Status</th>
          <th class="text-right p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $a): ?>
          <tr class="border-t">
            <td class="p-3 font-medium text-slate-800"><?= e($a['employee_name']) ?></td>
            <td class="p-3"><?= e($a['test_title']) ?></td>
            <td class="p-3 text-slate-600"><?= e($a['assigned_at']) ?></td>
            <td class="p-3 text-slate-600"><?= e($a['due_date']) ?: '—' ?></td>
            <td class="p-3 capitalize"><?= e($a['status']) ?></td>
            <td class="p-3 text-right space-x-3">
              <form method="post" action="<?= base_url('admin/assignments.php?action=reassign') ?>" class="inline">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <input type="datetime-local" name="due_date" class="border rounded px-2 py-1 text-xs" />
                <button class="text-primary-700 hover:underline text-sm" type="submit">Reassign</button>
              </form>
              <form method="post" action="<?= base_url('admin/assignments.php?action=remove') ?>" class="inline" onsubmit="return confirm('Remove this assignment?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="text-red-700 hover:underline text-sm" type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
