<?php
$title = 'Register';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $name = trim((string)post('name'));
    $email = trim((string)post('email'));
    $password = (string)post('password');
    $confirm = (string)post('confirm');
    $role = (string)(post('role') ?? 'employee');

    if ($name === '') { $errors[] = 'Name is required.'; }
    if (!validate_email($email)) { $errors[] = 'Valid email is required.'; }
    if (strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters.'; }
    if ($password !== $confirm) { $errors[] = 'Passwords do not match.'; }

    if (!$errors) {
        $pdo = getPDO();
        $exists = pdo_fetch_one($pdo, 'SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            $errors[] = 'Email already in use.';
        } else {
            if (!in_array($role, ['admin','employee'], true)) { $role = 'employee'; }
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            flash_set('success', 'Account created. Please login.');
            redirect('login.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="max-w-md mx-auto mt-8">
  <div class="bg-white border border-slate-200 rounded p-6 shadow-sm">
    <h1 class="text-xl font-semibold mb-4">Create account</h1>
    <?php if ($errors): ?>
      <ul class="mb-4 text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Name</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="text" name="name" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Email</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="email" name="email" required />
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
        <input class="w-full border rounded px-3 py-2 focus-ring" type="password" name="password" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Confirm Password</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="password" name="confirm" required />
      </div>
      <div>
        <button class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" type="submit">Register</button>
        <a class="ml-2 text-sm text-primary-700 hover:underline" href="<?= base_url('login.php') ?>">Back to login</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
