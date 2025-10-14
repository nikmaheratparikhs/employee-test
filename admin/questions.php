<?php
$title = 'Manage Questions';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$testId = (int)(get('test_id') ?? 0);
$test = $testId ? pdo_fetch_one($pdo, 'SELECT * FROM tests WHERE id = ?', [$testId]) : null;

$action = get('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    if ($action === 'create_question' && $testId) {
        $text = trim((string)post('question_text'));
        $type = (string)post('question_type');
        $points = (float)(post('points') ?? 1);
        $correctText = $type === 'text' ? trim((string)post('correct_text_answer')) : null;

        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO questions (test_id, question_text, question_type, points, correct_text_answer) VALUES (?, ?, ?, ?, ?)')
                ->execute([$testId, $text, $type, $points, $correctText]);
            $qid = (int)$pdo->lastInsertId();

            if ($type !== 'text') {
                // Exactly 4 choices
                $choices = [];
                for ($i = 1; $i <= 4; $i++) {
                    $ct = trim((string)post('choice_' . $i));
                    $isC = (int)(post('correct_' . $i) ? 1 : 0);
                    $choices[] = [$ct, $isC];
                }
                // Validation: at least one correct, and for single exactly one
                $numCorrect = array_sum(array_map(fn($c) => $c[1], $choices));
                if ($numCorrect === 0) {
                    throw new RuntimeException('Select at least one correct choice.');
                }
                if ($type === 'single' && $numCorrect !== 1) {
                    throw new RuntimeException('Single choice must have exactly one correct answer.');
                }
                foreach ($choices as [$ct, $isC]) {
                    $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)')
                        ->execute([$qid, $ct, $isC]);
                }
            }

            $pdo->commit();
            flash_set('success', 'Question added.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Failed to add question: ' . ($e->getMessage())) ;
        }
        redirect('admin/questions.php?test_id=' . $testId);
    }
    if ($action === 'delete_question' && $testId) {
        $id = (int)post('id');
        $pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
        flash_set('success', 'Question deleted.');
        redirect('admin/questions.php?test_id=' . $testId);
    }
    if ($action === 'update_question' && $testId) {
        $qid = (int)post('id');
        $text = trim((string)post('question_text'));
        $type = (string)post('question_type');
        $points = (float)(post('points') ?? 1);
        $correctText = $type === 'text' ? trim((string)post('correct_text_answer')) : null;

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE questions SET question_text=?, question_type=?, points=?, correct_text_answer=? WHERE id=? AND test_id=?')
                ->execute([$text, $type, $points, $correctText, $qid, $testId]);
            // Update choices for non-text: overwrite 4 choices
            if ($type !== 'text') {
                $pdo->prepare('DELETE FROM choices WHERE question_id = ?')->execute([$qid]);
                $choices = [];
                for ($i = 1; $i <= 4; $i++) {
                    $ct = trim((string)post('choice_' . $i));
                    $isC = (int)(post('correct_' . $i) ? 1 : 0);
                    $choices[] = [$ct, $isC];
                }
                $numCorrect = array_sum(array_map(fn($c) => $c[1], $choices));
                if ($numCorrect === 0) { throw new RuntimeException('Select at least one correct choice.'); }
                if ($type === 'single' && $numCorrect !== 1) { throw new RuntimeException('Single choice must have exactly one correct answer.'); }
                foreach ($choices as [$ct, $isC]) {
                    $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)')
                        ->execute([$qid, $ct, $isC]);
                }
            } else {
                // If text question, remove all choices
                $pdo->prepare('DELETE FROM choices WHERE question_id = ?')->execute([$qid]);
            }
            $pdo->commit();
            flash_set('success', 'Question updated.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Failed to update question: ' . $e->getMessage());
        }
        redirect('admin/questions.php?test_id=' . $testId);
    }
}

// When test not selected, show a selector
if (!$testId || !$test) {
    $tests = pdo_fetch_all($pdo, 'SELECT id, title FROM tests ORDER BY title ASC');
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="max-w-md mx-auto">
      <h1 class="text-xl font-semibold mb-3">Select a Test</h1>
      <form method="get" class="bg-white border border-slate-200 rounded p-4 space-y-3">
        <div>
          <label class="block text-sm text-slate-600 mb-1">Test</label>
          <select name="test_id" class="w-full border rounded px-3 py-2">
            <?php foreach ($tests as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= e($t['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Open Questions</button>
        </div>
      </form>
    </div>
    <?php include __DIR__ . '/../includes/footer.php';
    return; }

// Load questions for selected test
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
  <div class="text-sm text-slate-500 mt-1">Managing: <span class="text-primary-700 font-medium"><?= e($test['title']) ?></span></div>
</div>

<div class="bg-white border border-slate-200 rounded p-4 mb-6">
  <h2 class="font-semibold mb-3">Add Question (with 4 choices)</h2>
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?php for ($i=1; $i<=4; $i++): ?>
        <div class="border rounded p-3">
          <label class="block text-xs text-slate-600 mb-1">Choice <?= $i ?></label>
          <input name="choice_<?= $i ?>" class="w-full border rounded px-3 py-2 focus-ring" placeholder="Answer option" />
          <label class="mt-2 inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="correct_<?= $i ?>" class="border rounded"> Correct
          </label>
        </div>
      <?php endfor; ?>
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
        <div class="space-x-3">
          <a href="<?= base_url('admin/question_edit.php?test_id=' . $testId . '&id=' . (int)$q['id']) ?>" class="text-slate-700 hover:underline text-sm">Edit</a>
          <form method="post" action="<?= base_url('admin/questions.php?action=delete_question&test_id=' . $testId) ?>" class="inline" onsubmit="return confirm('Delete this question?')">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
            <button class="text-red-700 hover:underline text-sm" type="submit">Delete</button>
          </form>
        </div>
      </div>

      <?php if ($q['question_type'] !== 'text'): ?>
        <div class="mt-3">
          <div class="text-xs text-slate-500 mb-1">Choices</div>
          <ul class="space-y-1 list-disc list-inside">
            <?php foreach ($choicesByQ[$q['id']] ?? [] as $ch): ?>
              <li class="<?= $ch['is_correct'] ? 'text-green-700 font-medium' : '' ?>"><?= e($ch['choice_text']) ?> <?= $ch['is_correct'] ? '(correct)' : '' ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <div class="mt-2 text-xs text-slate-500">Text answer: <?= e($q['correct_text_answer']) ?: '—' ?></div>
      <?php endif; ?>
    </div>

    
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
