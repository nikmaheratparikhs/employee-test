<?php
$title = 'Manage Tests';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

// Handle create/update/delete
$action = get('action');
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
  <a href="<?= base_url('admin/test_create.php') ?>" class="px-4 py-2 rounded bg-primary-600 text-white hover:bg-primary-700 transition">New Test</a>
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
        <?php if (!$tests): ?>
          <tr>
            <td colspan="6" class="p-6 text-center text-slate-500">No Data</td>
          </tr>
        <?php endif; ?>
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
              <a class="ml-3 text-slate-700 hover:underline" href="<?= base_url('admin/test_edit.php?id=' . $t['id']) ?>">Edit</a>
              <form method="post" action="<?= base_url('admin/tests.php?action=delete') ?>" class="inline" onsubmit="return confirm('Delete this test?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                <button class="ml-3 text-red-700 hover:underline" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
