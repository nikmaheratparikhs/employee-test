<?php
$title = 'New Assignment';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$employees = pdo_fetch_all($pdo, 'SELECT id, name, email FROM users WHERE role = "employee" ORDER BY name ASC');
$tests = pdo_fetch_all($pdo, 'SELECT id, title FROM tests WHERE is_active = 1 ORDER BY title ASC');

$prefillEmp = (int)(get('employee_id') ?? 0);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $emp = (int)post('employee_id');
    $test = (int)post('test_id');
    $limit = (int)(post('attempt_limit') ?? 1);
    try {
        $pdo->prepare('INSERT INTO assignments (test_id, employee_id, assigned_by, attempt_limit) VALUES (?, ?, ?, ?)')
            ->execute([$test, $emp, $_SESSION['user']['id'], $limit]);
        flash_set('success', 'Assignment created.');
        redirect('admin/assignments.php?employee_id=' . $emp);
    } catch (Throwable $e) {
        $errors[] = 'Could not assign (maybe already assigned).';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-2xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">New Assignment</h1>
    <a href="<?= base_url('admin/assignments.php') ?>" class="text-sm text-primary-700 hover:underline">Back</a>
  </div>
  <form method="post" class="bg-white border border-slate-200 rounded p-6 grid grid-cols-1 gap-4">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($errors): ?>
      <ul class="text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Employee</label>
      <select name="employee_id" class="w-full border rounded px-3 py-2">
        <?php foreach ($employees as $emp): ?>
          <option value="<?= (int)$emp['id'] ?>" <?= $prefillEmp === (int)$emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?> (<?= e($emp['email']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Test</label>
      <select name="test_id" class="w-full border rounded px-3 py-2">
        <?php foreach ($tests as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Attempt limit</label>
      <input type="number" min="1" name="attempt_limit" value="1" class="w-full border rounded px-3 py-2 focus-ring" />
    </div>
    <div>
      <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Create Assignment</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
