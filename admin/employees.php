<?php
$title = 'Manage Employees';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$action = get('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    if ($action === 'create') {
        $name = trim((string)post('name'));
        $email = trim((string)post('email'));
        $password = (string)post('password');
        $interview_date = trim((string)post('interview_date'));
        if (!$name || !validate_email($email) || strlen($password) < 6) {
            flash_set('error', 'Please provide valid name, email, and password (>= 6).');
        } else {
            $exists = pdo_fetch_one($pdo, 'SELECT id FROM users WHERE email = ?', [$email]);
            if ($exists) {
                flash_set('error', 'Email already exists.');
            } else {
                $pdo->prepare('INSERT INTO users (name, email, password_hash, role, interview_date) VALUES (?, ?, ?, "employee", ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $interview_date ?: null]);
                flash_set('success', 'Employee created.');
            }
        }
        redirect('admin/employees.php');
    }
    if ($action === 'update') {
        $id = (int)post('id');
        $name = trim((string)post('name'));
        $email = trim((string)post('email'));
        $interview_date = trim((string)post('interview_date'));
        $is_active = (int)(post('is_active') ? 1 : 0);
        $pdo->prepare('UPDATE users SET name=?, email=?, interview_date=?, is_active=? WHERE id=? AND role="employee"')
            ->execute([$name, $email, $interview_date ?: null, $is_active, $id]);
        flash_set('success', 'Employee updated.');
        redirect('admin/employees.php');
    }
    if ($action === 'delete') {
        $id = (int)post('id');
        $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "employee"')->execute([$id]);
        flash_set('success', 'Employee deleted.');
        redirect('admin/employees.php');
    }
}

$employees = pdo_fetch_all($pdo, 'SELECT * FROM users WHERE role = "employee" ORDER BY created_at DESC');
include __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Employees</h1>
  <label for="createDialog" class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition cursor-pointer">New Employee</label>
</div>

<input type="checkbox" id="createDialog" class="hidden" />
<div class="fixed inset-0 bg-black/30 hidden items-center justify-center p-4" x-data x-show="$el.previousElementSibling.checked" x-transition>
  <div class="bg-white rounded shadow-xl w-full max-w-lg p-6">
    <h2 class="font-semibold mb-4">Create Employee</h2>
    <form method="post" action="<?= base_url('admin/employees.php?action=create') ?>" class="grid grid-cols-1 gap-3">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Name</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" name="name" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Email</label>
        <input type="email" class="w-full border rounded px-3 py-2 focus-ring" name="email" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Password</label>
        <input type="password" class="w-full border rounded px-3 py-2 focus-ring" name="password" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Interview date</label>
        <input type="datetime-local" class="w-full border rounded px-3 py-2 focus-ring" name="interview_date" />
      </div>
      <div class="mt-2 flex justify-end gap-2">
        <label for="createDialog" class="px-4 py-2 rounded border">Cancel</label>
        <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Create</button>
      </div>
    </form>
  </div>
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
              <label for="edit-<?= $u['id'] ?>" class="text-slate-700 hover:underline cursor-pointer">Edit</label>
              <form method="post" action="<?= base_url('admin/employees.php?action=delete') ?>" class="inline" onsubmit="return confirm('Delete this employee?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                <button class="ml-3 text-red-700 hover:underline" type="submit">Delete</button>
              </form>
              <a class="ml-3 text-primary-700 hover:underline" href="<?= base_url('admin/assignments.php?employee_id=' . $u['id']) ?>">Assign Tests</a>
            </td>
          </tr>

          <input type="checkbox" id="edit-<?= $u['id'] ?>" class="hidden" />
          <div class="fixed inset-0 bg-black/30 hidden items-center justify-center p-4" x-data x-show="document.getElementById('edit-<?= $u['id'] ?>').checked" x-transition>
            <div class="bg-white rounded shadow-xl w-full max-w-lg p-6">
              <h2 class="font-semibold mb-4">Edit Employee</h2>
              <form method="post" action="<?= base_url('admin/employees.php?action=update') ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <div class="grid grid-cols-1 gap-3">
                  <div>
                    <label class="block text-sm text-slate-600 mb-1">Name</label>
                    <input class="w-full border rounded px-3 py-2 focus-ring" name="name" value="<?= e($u['name']) ?>" required />
                  </div>
                  <div>
                    <label class="block text-sm text-slate-600 mb-1">Email</label>
                    <input type="email" class="w-full border rounded px-3 py-2 focus-ring" name="email" value="<?= e($u['email']) ?>" required />
                  </div>
                  <div>
                    <label class="block text-sm text-slate-600 mb-1">Interview date</label>
                    <input type="datetime-local" class="w-full border rounded px-3 py-2 focus-ring" name="interview_date" value="<?= e(str_replace(' ', 'T', (string)$u['interview_date'])) ?>" />
                  </div>
                  <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" class="border rounded" <?= $u['is_active'] ? 'checked' : '' ?> /> Active
                  </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                  <label for="edit-<?= $u['id'] ?>" class="px-4 py-2 rounded border">Cancel</label>
                  <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Save</button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
