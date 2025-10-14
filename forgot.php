<?php
$title = 'Forgot password';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$sent = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $email = trim((string)post('email'));
    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email.';
    }

    if (!$errors) {
        $pdo = getPDO();
        $user = pdo_fetch_one($pdo, 'SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
                ->execute([$user['id'], $token, $expires]);
            // In real app send email; for XAMPP demo, show link directly
            flash_set('success', 'Password reset link: ' . base_url('reset.php?token=' . $token));
        }
        $sent = true;
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="max-w-md mx-auto mt-8">
  <div class="bg-white border border-slate-200 rounded p-6 shadow-sm">
    <h1 class="text-xl font-semibold mb-4">Forgot password</h1>
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
        <button class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" type="submit">Send reset link</button>
        <a class="ml-2 text-sm text-primary-700 hover:underline" href="<?= base_url('login.php') ?>">Back to login</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
