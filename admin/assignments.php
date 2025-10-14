<?php
$title = 'Manage Assignments';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$employeeId = (int)(get('employee_id') ?? 0);
$employees = pdo_fetch_all($pdo, 'SELECT id, name, email FROM users WHERE role = "employee" ORDER BY name ASC');
$tests = pdo_fetch_all($pdo, 'SELECT id, title FROM tests WHERE is_active = 1 ORDER BY title ASC');

$action = get('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    if ($action === 'assign') {
        $emp = (int)post('employee_id');
        $test = (int)post('test_id');
        $due = trim((string)post('due_date'));
        $limit = (int)(post('attempt_limit') ?? 1);
        try {
            $pdo->prepare('INSERT INTO assignments (test_id, employee_id, assigned_by, due_date, attempt_limit) VALUES (?, ?, ?, ?, ?)')
                ->execute([$test, $emp, $_SESSION['user']['id'], $due ?: null, $limit]);
            flash_set('success', 'Assignment created.');
        } catch (Throwable $e) {
            flash_set('error', 'Could not assign (maybe already assigned).');
        }
        redirect('admin/assignments.php?employee_id=' . $emp);
    }
    if ($action === 'remove') {
        $id = (int)post('id');
        $pdo->prepare('DELETE FROM assignments WHERE id = ?')->execute([$id]);
        flash_set('success', 'Assignment removed.');
        redirect('admin/assignments.php');
    }
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
  <h2 class="font-semibold mb-3">Assign a Test</h2>
  <form method="post" action="<?= base_url('admin/assignments.php?action=assign') ?>" class="grid grid-cols-1 md:grid-cols-5 gap-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600 mb-1">Employee</label>
      <select name="employee_id" class="w-full border rounded px-3 py-2">
        <?php foreach ($employees as $emp): ?>
          <option value="<?= (int)$emp['id'] ?>" <?= $currentEmp && $currentEmp['id'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?> (<?= e($emp['email']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600 mb-1">Test</label>
      <select name="test_id" class="w-full border rounded px-3 py-2">
        <?php foreach ($tests as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Due date</label>
      <input type="datetime-local" name="due_date" class="w-full border rounded px-3 py-2 focus-ring" />
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Attempt limit</label>
      <input type="number" min="1" name="attempt_limit" value="1" class="w-full border rounded px-3 py-2 focus-ring" />
    </div>
    <div class="md:col-span-5">
      <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Assign</button>
    </div>
  </form>
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
            <td class="p-3 text-right">
              <form method="post" action="<?= base_url('admin/assignments.php?action=remove') ?>" class="inline" onsubmit="return confirm('Remove this assignment?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="text-red-700 hover:underline" type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
