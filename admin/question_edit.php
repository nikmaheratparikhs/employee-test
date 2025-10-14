<?php
$title = 'Edit Question';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$testId = (int)(get('test_id') ?? 0);
$qid = (int)(get('id') ?? 0);
$test = pdo_fetch_one($pdo, 'SELECT * FROM tests WHERE id = ?', [$testId]);
$question = pdo_fetch_one($pdo, 'SELECT * FROM questions WHERE id = ? AND test_id = ?', [$qid, $testId]);
if (!$test || !$question) { flash_set('error', 'Question not found.'); redirect('admin/questions.php?test_id=' . $testId); }

$choices = pdo_fetch_all($pdo, 'SELECT * FROM choices WHERE question_id = ? ORDER BY id ASC', [$qid]);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf_or_fail();
  $text = trim((string)post('question_text'));
  $type = (string)post('question_type');
  $points = (float)(post('points') ?? 1);
  $correctText = $type === 'text' ? trim((string)post('correct_text_answer')) : null;

  $pdo->beginTransaction();
  try {
    $pdo->prepare('UPDATE questions SET question_text=?, question_type=?, points=?, correct_text_answer=? WHERE id=? AND test_id=?')
        ->execute([$text, $type, $points, $correctText, $qid, $testId]);

    if ($type !== 'text') {
      $pdo->prepare('DELETE FROM choices WHERE question_id = ?')->execute([$qid]);
      $numCorrect = 0;
      for ($i=1; $i<=4; $i++) {
        $ct = trim((string)post('choice_' . $i));
        $isC = (int)(post('correct_' . $i) ? 1 : 0);
        $numCorrect += $isC;
        $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)')
            ->execute([$qid, $ct, $isC]);
      }
      if ($numCorrect === 0) { throw new RuntimeException('Select at least one correct choice.'); }
      if ($type === 'single' && $numCorrect !== 1) { throw new RuntimeException('Single choice must have exactly one correct answer.'); }
    } else {
      $pdo->prepare('DELETE FROM choices WHERE question_id = ?')->execute([$qid]);
    }

    $pdo->commit();
    flash_set('success', 'Question updated.');
    redirect('admin/questions.php?test_id=' . $testId);
  } catch (Throwable $e) {
    $pdo->rollBack();
    $errors[] = 'Failed to update: ' . $e->getMessage();
  }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-3xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Edit Question</h1>
    <a href="<?= base_url('admin/questions.php?test_id=' . $testId) ?>" class="text-sm text-primary-700 hover:underline">Back</a>
  </div>

  <?php if ($errors): ?>
    <ul class="mb-4 text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" class="bg-white border border-slate-200 rounded p-6 grid grid-cols-1 gap-4">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="block text-sm text-slate-600 mb-1">Question</label>
      <textarea class="w-full border rounded px-3 py-2 focus-ring" name="question_text" required><?= e($question['question_text']) ?></textarea>
    </div>
    <div class="grid grid-cols-3 gap-3">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Type</label>
        <select name="question_type" class="w-full border rounded px-3 py-2">
          <?php foreach (['single'=>'Single choice','multiple'=>'Multiple choice','text'=>'Text answer'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $question['question_type']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Points</label>
        <input type="number" step="0.5" min="0" name="points" value="<?= e($question['points']) ?>" class="w-full border rounded px-3 py-2 focus-ring" />
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Correct text (if text type)</label>
        <input type="text" name="correct_text_answer" value="<?= e((string)$question['correct_text_answer']) ?>" class="w-full border rounded px-3 py-2 focus-ring" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?php for ($i=1; $i<=4; $i++): $ct = $choices[$i-1]['choice_text'] ?? ''; $isC = isset($choices[$i-1]) ? (int)$choices[$i-1]['is_correct'] : 0; ?>
        <div class="border rounded p-3">
          <label class="block text-xs text-slate-600 mb-1">Choice <?= $i ?></label>
          <input name="choice_<?= $i ?>" class="w-full border rounded px-3 py-2 focus-ring" value="<?= e($ct) ?>" />
          <label class="mt-2 inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="correct_<?= $i ?>" class="border rounded" <?= $isC ? 'checked' : '' ?>> Correct
          </label>
        </div>
      <?php endfor; ?>
    </div>

    <div class="flex justify-end">
      <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Save</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
