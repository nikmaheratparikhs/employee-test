<?php
$title = 'Create Test';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $titleIn = trim((string)post('title'));
    $description = trim((string)post('description'));
    $category = trim((string)post('category'));
    $difficulty = (string)post('difficulty');
    $time_limit = (int)(post('time_limit_minutes') ?? 0);
    $is_active = (int)(post('is_active') ? 1 : 0);
    if ($titleIn === '') { $errors[] = 'Title is required.'; }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO tests (title, description, category, difficulty, time_limit_minutes, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$titleIn, $description, $category, $difficulty, $time_limit ?: null, $is_active, $_SESSION['user']['id']]);
        flash_set('success', 'Test created.');
        redirect('admin/tests.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-2xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Create Test</h1>
    <a href="<?= base_url('admin/tests.php') ?>" class="text-sm text-primary-700 hover:underline">Back</a>
  </div>
  <form method="post" class="bg-white border border-slate-200 rounded p-6 grid grid-cols-1 gap-4">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($errors): ?>
      <ul class="text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Title</label>
      <input class="w-full border rounded px-3 py-2 focus-ring" name="title" required />
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Description</label>
      <textarea class="w-full border rounded px-3 py-2 focus-ring" name="description"></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Category</label>
        <input class="w-full border rounded px-3 py-2 focus-ring" name="category" />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Difficulty</label>
        <select class="w-full border rounded px-3 py-2" name="difficulty">
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Time limit (minutes)</label>
        <input type="number" min="0" class="w-full border rounded px-3 py-2 focus-ring" name="time_limit_minutes" />
      </div>
      <label class="flex items-center gap-2 mt-6">
        <input type="checkbox" name="is_active" class="border rounded" checked /> Active
      </label>
    </div>
    <div>
      <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Create</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
