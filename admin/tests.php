<?php
$title = 'Manage Tests';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

// Handle create/update/delete
$action = get('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $titleIn = trim((string)post('title'));
    $description = trim((string)post('description'));
    $category = trim((string)post('category'));
    $difficulty = (string)post('difficulty');
    $time_limit = (int)(post('time_limit_minutes') ?? 0);
    $is_active = (int)(post('is_active') ? 1 : 0);

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO tests (title, description, category, difficulty, time_limit_minutes, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$titleIn, $description, $category, $difficulty, $time_limit ?: null, $is_active, $_SESSION['user']['id']]);
        flash_set('success', 'Test created.');
        redirect('admin/tests.php');
    }
    if ($action === 'update') {
        $id = (int)post('id');
        $stmt = $pdo->prepare('UPDATE tests SET title=?, description=?, category=?, difficulty=?, time_limit_minutes=?, is_active=? WHERE id=?');
        $stmt->execute([$titleIn, $description, $category, $difficulty, $time_limit ?: null, $is_active, $id]);
        flash_set('success', 'Test updated.');
        redirect('admin/tests.php');
    }
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    $id = (int)post('id');
    $pdo->prepare('DELETE FROM tests WHERE id = ?')->execute([$id]);
    flash_set('success', 'Test deleted.');
    redirect('admin/tests.php');
}

$tests = pdo_fetch_all($pdo, 'SELECT * FROM tests ORDER BY created_at DESC');
include __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Tests</h1>
  <label for="createDialog" class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition cursor-pointer">New Test</label>
</div>

<input type="checkbox" id="createDialog" class="hidden" />
<div class="fixed inset-0 bg-black/30 hidden items-center justify-center p-4" x-data x-show="$el.previousElementSibling.checked" x-transition>
  <div class="bg-white rounded shadow-xl w-full max-w-lg p-6">
    <h2 class="font-semibold mb-4">Create Test</h2>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
      <input type="hidden" name="action" value="create" />
      <div class="grid grid-cols-1 gap-3">
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
      </div>
      <div class="mt-4 flex justify-end gap-2">
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
          <th class="text-left p-3">Title</th>
          <th class="text-left p-3">Category</th>
          <th class="text-left p-3">Difficulty</th>
          <th class="text-left p-3">Time</th>
          <th class="text-left p-3">Active</th>
          <th class="text-right p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tests as $t): ?>
          <tr class="border-t">
            <td class="p-3 font-medium text-slate-800"><?= e($t['title']) ?></td>
            <td class="p-3"><?= e($t['category']) ?></td>
            <td class="p-3 capitalize"><?= e($t['difficulty']) ?></td>
            <td class="p-3"><?= e($t['time_limit_minutes'] ?? '-') ?></td>
            <td class="p-3">
              <span class="px-2 py-0.5 rounded text-xs <?= $t['is_active'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>"><?= $t['is_active'] ? 'Yes' : 'No' ?></span>
            </td>
            <td class="p-3 text-right">
              <a class="text-primary-700 hover:underline" href="<?= base_url('admin/questions.php?test_id=' . $t['id']) ?>">Questions</a>
              <label for="edit-<?= $t['id'] ?>" class="ml-3 text-slate-700 hover:underline cursor-pointer">Edit</label>
              <form method="post" action="<?= base_url('admin/tests.php?action=delete') ?>" class="inline" onsubmit="return confirm('Delete this test?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                <button class="ml-3 text-red-700 hover:underline" type="submit">Delete</button>
              </form>
            </td>
          </tr>

          <input type="checkbox" id="edit-<?= $t['id'] ?>" class="hidden" />
          <div class="fixed inset-0 bg-black/30 hidden items-center justify-center p-4" x-data x-show="document.getElementById('edit-<?= $t['id'] ?>').checked" x-transition>
            <div class="bg-white rounded shadow-xl w-full max-w-lg p-6">
              <h2 class="font-semibold mb-4">Edit Test</h2>
              <form method="post" action="<?= base_url('admin/tests.php?action=update') ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                <div class="grid grid-cols-1 gap-3">
                  <div>
                    <label class="block text-sm text-slate-600 mb-1">Title</label>
                    <input class="w-full border rounded px-3 py-2 focus-ring" name="title" value="<?= e($t['title']) ?>" required />
                  </div>
                  <div>
                    <label class="block text-sm text-slate-600 mb-1">Description</label>
                    <textarea class="w-full border rounded px-3 py-2 focus-ring" name="description"><?= e($t['description']) ?></textarea>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-sm text-slate-600 mb-1">Category</label>
                      <input class="w-full border rounded px-3 py-2 focus-ring" name="category" value="<?= e($t['category']) ?>" />
                    </div>
                    <div>
                      <label class="block text-sm text-slate-600 mb-1">Difficulty</label>
                      <select class="w-full border rounded px-3 py-2" name="difficulty">
                        <?php foreach (['beginner','intermediate','advanced'] as $d): ?>
                          <option value="<?= $d ?>" <?= $t['difficulty'] === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-sm text-slate-600 mb-1">Time limit (minutes)</label>
                      <input type="number" min="0" class="w-full border rounded px-3 py-2 focus-ring" name="time_limit_minutes" value="<?= e($t['time_limit_minutes']) ?>" />
                    </div>
                    <label class="flex items-center gap-2 mt-6">
                      <input type="checkbox" name="is_active" class="border rounded" <?= $t['is_active'] ? 'checked' : '' ?> /> Active
                    </label>
                  </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                  <label for="edit-<?= $t['id'] ?>" class="px-4 py-2 rounded border">Cancel</label>
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
