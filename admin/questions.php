<?php
$title = 'Manage Questions';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$testId = (int)(get('test_id') ?? 0);
$test = $testId ? pdo_fetch_one($pdo, 'SELECT * FROM tests WHERE id = ?', [$testId]) : null;
if (!$test) {
    flash_set('error', 'Test not found.');
    redirect('admin/tests.php');
}

$action = get('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    if ($action === 'create_question') {
        $text = trim((string)post('question_text'));
        $type = (string)post('question_type');
        $points = (float)(post('points') ?? 1);
        $correctText = $type === 'text' ? trim((string)post('correct_text_answer')) : null;
        $pdo->prepare('INSERT INTO questions (test_id, question_text, question_type, points, correct_text_answer) VALUES (?, ?, ?, ?, ?)')
            ->execute([$testId, $text, $type, $points, $correctText]);
        flash_set('success', 'Question added.');
        redirect('admin/questions.php?test_id=' . $testId);
    }
    if ($action === 'delete_question') {
        $id = (int)post('id');
        $pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
        flash_set('success', 'Question deleted.');
        redirect('admin/questions.php?test_id=' . $testId);
    }
    if ($action === 'add_choice') {
        $qid = (int)post('question_id');
        $choice = trim((string)post('choice_text'));
        $isCorrect = (int)(post('is_correct') ? 1 : 0);
        $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)')
            ->execute([$qid, $choice, $isCorrect]);
        flash_set('success', 'Choice added.');
        redirect('admin/questions.php?test_id=' . $testId);
    }
    if ($action === 'delete_choice') {
        $cid = (int)post('choice_id');
        $pdo->prepare('DELETE FROM choices WHERE id = ?')->execute([$cid]);
        flash_set('success', 'Choice removed.');
        redirect('admin/questions.php?test_id=' . $testId);
    }
}

$questions = pdo_fetch_all($pdo, 'SELECT * FROM questions WHERE test_id = ? ORDER BY id ASC', [$testId]);
$choicesByQ = [];
if ($questions) {
    $ids = implode(',', array_map('intval', array_column($questions, 'id')));
    $rows = $ids ? $pdo->query('SELECT * FROM choices WHERE question_id IN (' . $ids . ') ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $row) {
        $choicesByQ[$row['question_id']][] = $row;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <a href="<?= base_url('admin/tests.php') ?>" class="text-sm text-primary-700 hover:underline">&larr; Back to Tests</a>
</div>
<h1 class="text-xl font-semibold mb-2">Questions for: <span class="text-primary-700"><?= e($test['title']) ?></span></h1>

<div class="bg-white border border-slate-200 rounded p-4 mb-6">
  <h2 class="font-semibold mb-3">Add Question</h2>
  <form method="post" action="<?= base_url('admin/questions.php?action=create_question&test_id=' . $testId) ?>" class="grid grid-cols-1 gap-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="block text-sm text-slate-600 mb-1">Question</label>
      <textarea class="w-full border rounded px-3 py-2 focus-ring" name="question_text" required></textarea>
    </div>
    <div class="grid grid-cols-3 gap-3">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Type</label>
        <select name="question_type" class="w-full border rounded px-3 py-2">
          <option value="single">Single choice</option>
          <option value="multiple">Multiple choice</option>
          <option value="text">Text answer</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Points</label>
        <input type="number" step="0.5" min="0" name="points" value="1" class="w-full border rounded px-3 py-2 focus-ring" />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Correct text (if text type)</label>
        <input type="text" name="correct_text_answer" class="w-full border rounded px-3 py-2 focus-ring" />
      </div>
    </div>
    <div>
      <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Add Question</button>
    </div>
  </form>
</div>

<div class="space-y-4">
  <?php foreach ($questions as $q): ?>
    <div class="bg-white border border-slate-200 rounded p-4">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-slate-500 text-xs">Question #<?= (int)$q['id'] ?> • <?= e(ucfirst($q['question_type'])) ?> • <?= e($q['points']) ?> pts</div>
          <div class="font-medium text-slate-800"><?= nl2br(e($q['question_text'])) ?></div>
        </div>
        <form method="post" action="<?= base_url('admin/questions.php?action=delete_question&test_id=' . $testId) ?>" onsubmit="return confirm('Delete this question?')">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
          <button class="text-red-700 hover:underline text-sm" type="submit">Delete</button>
        </form>
      </div>

      <?php if ($q['question_type'] !== 'text'): ?>
        <div class="mt-3">
          <div class="text-xs text-slate-500 mb-1">Choices</div>
          <ul class="space-y-1">
            <?php foreach ($choicesByQ[$q['id']] ?? [] as $ch): ?>
              <li class="flex items-center justify-between">
                <span class="<?= $ch['is_correct'] ? 'text-green-700' : '' ?>">- <?= e($ch['choice_text']) ?> <?= $ch['is_correct'] ? '(correct)' : '' ?></span>
                <form method="post" action="<?= base_url('admin/questions.php?action=delete_choice&test_id=' . $testId) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="choice_id" value="<?= (int)$ch['id'] ?>">
                  <button class="text-sm text-red-700 hover:underline" type="submit">Remove</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>

          <form method="post" action="<?= base_url('admin/questions.php?action=add_choice&test_id=' . $testId) ?>" class="mt-3 grid grid-cols-6 gap-2 items-end">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
            <div class="col-span-5">
              <label class="block text-xs text-slate-600 mb-1">Choice text</label>
              <input name="choice_text" class="w-full border rounded px-3 py-2 focus-ring" required />
            </div>
            <label class="flex items-center gap-2">
              <input type="checkbox" name="is_correct" class="border rounded" /> Correct
            </label>
            <div class="col-span-6">
              <button class="px-3 py-2 rounded bg-slate-800 text-white text-sm" type="submit">Add Choice</button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <div class="mt-2 text-xs text-slate-500">Text answer configured: <?= e($q['correct_text_answer']) ?: '—' ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
