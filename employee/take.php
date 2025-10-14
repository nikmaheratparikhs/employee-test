<?php
$title = 'Take Test';
require_once __DIR__ . '/../includes/functions.php';
require_role('employee');
$pdo = getPDO();

$assignmentId = (int)(get('assignment_id') ?? 0);
$assignment = pdo_fetch_one($pdo, 'SELECT a.*, t.title, t.time_limit_minutes FROM assignments a JOIN tests t ON t.id = a.test_id WHERE a.id = ? AND a.employee_id = ?', [$assignmentId, $_SESSION['user']['id']]);
if (!$assignment) {
    flash_set('error', 'Assignment not found.');
    redirect('employee/assignments.php');
}

// Start or get active attempt
$attempt = pdo_fetch_one($pdo, 'SELECT * FROM attempts WHERE assignment_id = ? AND submitted_at IS NULL ORDER BY id DESC LIMIT 1', [$assignmentId]);
if (!$attempt) {
    $pdo->prepare('INSERT INTO attempts (assignment_id) VALUES (?)')->execute([$assignmentId]);
    $attempt = pdo_fetch_one($pdo, 'SELECT * FROM attempts WHERE assignment_id = ? AND submitted_at IS NULL ORDER BY id DESC LIMIT 1', [$assignmentId]);
    $pdo->prepare('UPDATE assignments SET status = "in_progress" WHERE id = ?')->execute([$assignmentId]);
}

// Load questions 
$questions = pdo_fetch_all($pdo, 'SELECT * FROM questions WHERE test_id = ? ORDER BY id ASC', [$assignment['test_id']]);
$choicesByQ = [];
if ($questions) {
    $ids = implode(',', array_map('intval', array_column($questions, 'id')));
    $rows = $ids ? $pdo->query('SELECT * FROM choices WHERE question_id IN (' . $ids . ') ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $row) {
        $choicesByQ[$row['question_id']][] = $row;
    }
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();

    $pdo->beginTransaction();
    try {
        // Score based purely on number of questions: 1 point per correct answer
        $totalPoints = count($questions);
        $score = 0;
        foreach ($questions as $q) {
            $qid = (int)$q['id'];
            $answerText = null;
            $pdo->prepare('INSERT INTO answers (attempt_id, question_id, answer_text) VALUES (?, ?, ?)')
                ->execute([$attempt['id'], $qid, null]);
            $answerId = (int)$pdo->lastInsertId();

            if ($q['question_type'] === 'text') {
                $answerText = trim((string)post('q_' . $qid));
                $pdo->prepare('UPDATE answers SET answer_text = ? WHERE id = ?')->execute([$answerText, $answerId]);
                if ($q['correct_text_answer'] !== null && $answerText !== '') {
                    if (mb_strtolower(trim($q['correct_text_answer'])) === mb_strtolower(trim($answerText))) {
                        $score += 1;
                    }
                }
            } else if ($q['question_type'] === 'single') {
                $selected = (int)(post('q_' . $qid) ?? 0);
                if ($selected) {
                    $pdo->prepare('INSERT INTO answer_choices (answer_id, choice_id) VALUES (?, ?)')->execute([$answerId, $selected]);
                    // correct?
                    $choice = pdo_fetch_one($pdo, 'SELECT is_correct FROM choices WHERE id = ?', [$selected]);
                    if ($choice && (int)$choice['is_correct'] === 1) {
                        $score += 1;
                    }
                }
            } else if ($q['question_type'] === 'multiple') {
                $selected = (array)(post('q_' . $qid) ?? []);
                $selected = array_map('intval', $selected);
                foreach ($selected as $cid) {
                    $pdo->prepare('INSERT INTO answer_choices (answer_id, choice_id) VALUES (?, ?)')->execute([$answerId, $cid]);
                }
                // Scoring: full points only if set matches exactly
                $correctIds = array_map('intval', array_column($choicesByQ[$qid] ?? [], 'id', 'id'));
                $correctIds = array_map('intval', array_column(array_filter(($choicesByQ[$qid] ?? []), fn($c) => (int)$c['is_correct'] === 1), 'id'));
                sort($selected);
                sort($correctIds);
                if ($selected === $correctIds && count($correctIds) > 0) {
                    $score += 1;
                }
            }
        }
        $percent = $totalPoints > 0 ? round(($score / $totalPoints) * 100, 2) : 0.0;
        $pdo->prepare('UPDATE attempts SET submitted_at = NOW(), score_decimal = ?, total_points = ?, percent = ? WHERE id = ?')
            ->execute([(int)$score, (int)$totalPoints, $percent, $attempt['id']]);
        $pdo->prepare('UPDATE assignments SET status = "completed" WHERE id = ?')->execute([$assignmentId]);
        $pdo->commit();
        flash_set('success', 'Test submitted.');
        redirect('employee/history.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash_set('error', 'Submission failed. Please try again.');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Take Test: <?= e($assignment['title']) ?></h1>
  <?php if ($assignment['time_limit_minutes']): ?>
    <div class="text-sm text-slate-600">Time limit: <?= (int)$assignment['time_limit_minutes'] ?> min</div>
  <?php endif; ?>
</div>

<form method="post" class="space-y-6">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php foreach ($questions as $q): ?>
    <div class="bg-white border border-slate-200 rounded p-4">
      <div class="text-slate-500 text-xs mb-1">Question #<?= (int)$q['id'] ?> • <?= e(ucfirst($q['question_type'])) ?></div>
      <div class="font-medium text-slate-800 mb-3"><?= nl2br(e($q['question_text'])) ?></div>
      <?php if ($q['question_type'] === 'text'): ?>
        <textarea class="w-full border rounded px-3 py-2 focus-ring" name="q_<?= (int)$q['id'] ?>" rows="3" placeholder="Type your answer..."></textarea>
      <?php elseif ($q['question_type'] === 'single'): ?>
        <div class="space-y-2">
          <?php foreach ($choicesByQ[$q['id']] ?? [] as $ch): ?>
            <label class="flex items-center gap-2">
              <input type="radio" name="q_<?= (int)$q['id'] ?>" value="<?= (int)$ch['id'] ?>" class="border rounded" />
              <span><?= e($ch['choice_text']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="space-y-2">
          <?php foreach ($choicesByQ[$q['id']] ?? [] as $ch): ?>
            <label class="flex items-center gap-2">
              <input type="checkbox" name="q_<?= (int)$q['id'] ?>[]" value="<?= (int)$ch['id'] ?>" class="border rounded" />
              <span><?= e($ch['choice_text']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="flex justify-end">
    <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Submit Test</button>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
