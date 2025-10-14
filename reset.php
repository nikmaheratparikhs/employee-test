<?php
$title = 'Reset password';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pdo = getPDO();
$token = (string)(get('token') ?? '');
$errors = [];

$reset = null;
if ($token) {
    $reset = pdo_fetch_one($pdo, 'SELECT pr.*, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()', [$token]);
}

if (!$reset) {
    flash_set('error', 'Invalid or expired reset link.');
    redirect('forgot.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $password = (string)post('password');
    $confirm = (string)post('confirm');

    if (strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters.'; }
    if ($password !== $confirm) { $errors[] = 'Passwords do not match.'; }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')
                ->execute([$reset['id']]);
            $pdo->commit();
            flash_set('success', 'Password reset. You can now login.');
            redirect('login.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Unexpected error, please try again.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="max-w-md mx-auto mt-8">
  <div class="bg-white border border-slate-200 rounded p-6 shadow-sm">
    <h1 class="text-xl font-semibold mb-4">Set a new password</h1>
    <?php if ($errors): ?>
      <ul class="mb-4 text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-sm text-slate-600 mb-1">New password</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="password" name="password" required />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Confirm new password</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" type="password" name="confirm" required />
      </div>
      <div>
        <button class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition" type="submit">Reset password</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
