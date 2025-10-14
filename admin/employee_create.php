<?php
$title = 'Create Employee';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $name = trim((string)post('name'));
    $email = trim((string)post('email'));
    $password = (string)post('password');
    $role = (string)(post('role') ?? 'employee');
    $interview_date = trim((string)post('interview_date'));

    if ($name === '' || !validate_email($email) || strlen($password) < 6) {
        $errors[] = 'Provide valid name, email and password (>= 6 chars).';
    }

    $exists = pdo_fetch_one($pdo, 'SELECT id FROM users WHERE email = ?', [$email]);
    if (!$errors && $exists) { $errors[] = 'Email already exists.'; }

    if (!$errors) {
        if (!in_array($role, ['admin','employee'], true)) { $role = 'employee'; }
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role, interview_date) VALUES (?, ?, ?, ?, ?)')
            ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $interview_date ?: null]);
        flash_set('success', 'Employee created.');
        redirect('admin/employees.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-2xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Create Employee</h1>
    <a href="<?= base_url('admin/employees.php') ?>" class="text-sm text-primary-700 hover:underline">Back</a>
  </div>
  <form method="post" class="bg-white border border-slate-200 rounded p-6 grid grid-cols-1 gap-4">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($errors): ?>
      <ul class="text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Name</label>
      <input class="w-full border rounded px-3 py-2 focus-ring" name="name" required />
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Email</label>
      <input type="email" class="w-full border rounded px-3 py-2 focus-ring" name="email" required />
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Role</label>
      <select class="w-full border rounded px-3 py-2" name="role">
        <option value="employee">Employee</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Password</label>
      <input type="password" class="w-full border rounded px-3 py-2 focus-ring" name="password" required />
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Interview date</label>
      <input type="datetime-local" class="w-full border rounded px-3 py-2 focus-ring" name="interview_date" />
    </div>
    <div>
      <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Create</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
