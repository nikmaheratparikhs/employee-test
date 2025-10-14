<?php
$title = 'Login';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $email = trim((string)post('email'));
    $password = (string)post('password');

    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email.';
    }

    if (!$errors) {
        $pdo = getPDO();
        $user = pdo_fetch_one($pdo, 'SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid credentials.';
        } else {
            login_user($user);
            flash_set('success', 'Welcome back!');
            redirect('index.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="max-w-md mx-auto mt-8">
  <div class="bg-white border border-slate-200 rounded p-6 shadow-sm">
    <h1 class="text-xl font-semibold mb-4">Sign in</h1>
    <?php if ($errors): ?>
      <ul class="mb-4 text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Email</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="email" name="email" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Password</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="password" name="password" required />
      </div>
      <div class="flex items-center justify-between">
        <button class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" type="submit">Login</button>
        <a class="text-sm text-primary-700 hover:underline" href="<?= base_url('forgot.php') ?>">Forgot password?</a>
      </div>
    </form>
    <p class="text-sm text-slate-500 mt-4">No account? <a href="<?= base_url('register.php') ?>" class="text-primary-700 hover:underline">Create one</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
