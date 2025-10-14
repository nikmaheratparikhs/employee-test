<?php
$title = 'Import Tests';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pdo = getPDO();

$errors = [];
$success = [];

function xlsx_col_to_index(string $ref): int {
    if (!preg_match('/([A-Z]+)(\d+)/', $ref, $m)) return 0;
    $letters = $m[1];
    $n = 0; for ($i = 0; $i < strlen($letters); $i++) { $n = $n * 26 + (ord($letters[$i]) - 64); }
    return $n - 1; // zero-based
}

function parse_xlsx_to_rows(string $filePath): array {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive PHP extension not enabled. Enable php_zip in XAMPP.');
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('Cannot open Excel file.');
    }

    // Load shared strings if present
    $sharedStrings = [];
    $ssIndex = $zip->locateName('xl/sharedStrings.xml');
    if ($ssIndex !== false) {
        $xml = @simplexml_load_string($zip->getFromIndex($ssIndex));
        if ($xml && isset($xml->si)) {
            foreach ($xml->si as $i => $si) {
                if (isset($si->t)) {
                    $sharedStrings[(int)$i] = (string)$si->t;
                } else if (isset($si->r)) {
                    $acc = '';
                    foreach ($si->r as $r) { $acc .= (string)$r->t; }
                    $sharedStrings[(int)$i] = $acc;
                }
            }
        }
    }

    // Resolve first sheet path
    $wbXml = @simplexml_load_string($zip->getFromName('xl/workbook.xml'));
    $relsXml = @simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
    if (!$wbXml || !$relsXml) {
        throw new RuntimeException('Invalid Excel structure.');
    }
    $wbXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $firstSheetRid = (string)$wbXml->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
    $sheetTarget = null;
    foreach ($relsXml->Relationship as $rel) {
        if ((string)$rel['Id'] === $firstSheetRid) { $sheetTarget = (string)$rel['Target']; break; }
    }
    if (!$sheetTarget) { $sheetTarget = 'worksheets/sheet1.xml'; }
    $sheetPath = 'xl/' . ltrim($sheetTarget, '/');

    $sheetXml = @simplexml_load_string($zip->getFromName($sheetPath));
    if (!$sheetXml) {
        throw new RuntimeException('Unable to read worksheet.');
    }

    $rows = [];
    foreach ($sheetXml->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $c) {
            $idx = xlsx_col_to_index((string)$c['r']);
            $t = (string)$c['t'];
            $val = '';
            if ($t === 's') {
                $si = isset($c->v) ? (int)$c->v : -1; $val = $sharedStrings[$si] ?? '';
            } else if ($t === 'inlineStr') {
                $val = isset($c->is->t) ? (string)$c->is->t : '';
            } else {
                $val = isset($c->v) ? (string)$c->v : '';
            }
            $line[$idx] = trim($val);
        }
        if (!empty($line)) { ksort($line); $rows[] = array_values($line); }
    }
    $zip->close();
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_fail();
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload an Excel .xlsx file.';
    } else {
        $tmp = $_FILES['file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $errors[] = 'Invalid file type. Please upload an .xlsx file.';
        } else {
            try {
                $allRows = parse_xlsx_to_rows($tmp);
            } catch (Throwable $e) {
                $errors[] = 'Failed to read Excel: ' . $e->getMessage();
                $allRows = [];
            }
            if ($allRows) {
                $expected = ['test_title','test_description','test_category','test_difficulty','test_time_limit_minutes','question_text','question_type','question_points','choice_1','choice_1_correct','choice_2','choice_2_correct','choice_3','choice_3_correct','choice_4','choice_4_correct','correct_text_answer'];
                $normalize = fn($s) => strtolower(trim((string)$s));
                $header = array_map($normalize, $allRows[0] ?? []);
                if ($header !== $expected) {
                    $errors[] = 'Invalid header in Excel. Please use the sample Excel provided.';
                } else {
                    $pdo->beginTransaction();
                    try {
                        $testIdMap = [];
                        $createdTests = 0; $createdQuestions = 0;
                        for ($r = 1; $r < count($allRows); $r++) {
                            $row = $allRows[$r];
                            // normalize length
                            $row = array_slice($row, 0, count($expected));
                            if (count($row) < count($expected)) { $row = array_pad($row, count($expected), ''); }
                            $data = [];
                            foreach ($expected as $i => $key) { $data[$key] = trim((string)($row[$i] ?? '')); }

                            $keyTitle = trim($data['test_title']);
                            if ($keyTitle === '') { continue; }
                            if (!isset($testIdMap[$keyTitle])) {
                                $stmt = $pdo->prepare('INSERT INTO tests (title, description, category, difficulty, time_limit_minutes, is_active, created_by) VALUES (?,?,?,?,?,1,?)');
                                $stmt->execute([
                                    $data['test_title'],
                                    $data['test_description'] ?: null,
                                    $data['test_category'] ?: null,
                                    in_array($data['test_difficulty'], ['beginner','intermediate','advanced'], true) ? $data['test_difficulty'] : 'beginner',
                                    is_numeric($data['test_time_limit_minutes']) ? (int)$data['test_time_limit_minutes'] : null,
                                    $_SESSION['user']['id']
                                ]);
                                $testIdMap[$keyTitle] = (int)$pdo->lastInsertId();
                                $createdTests++;
                            }
                            $testId = $testIdMap[$keyTitle];

                            $qType = in_array(strtolower($data['question_type']), ['single','multiple','text'], true) ? strtolower($data['question_type']) : 'single';
                            $qPoints = is_numeric($data['question_points']) ? (float)$data['question_points'] : 1;
                            $pdo->prepare('INSERT INTO questions (test_id, question_text, question_type, points, correct_text_answer) VALUES (?,?,?,?,?)')
                                ->execute([$testId, $data['question_text'], $qType, $qPoints, $data['correct_text_answer'] ?: null]);
                            $qid = (int)$pdo->lastInsertId();
                            $createdQuestions++;

                            if ($qType !== 'text') {
                                for ($i = 1; $i <= 4; $i++) {
                                    $ct = trim((string)$data['choice_'.$i]);
                                    if ($ct === '') { continue; }
                                    $flag = strtolower(trim((string)$data['choice_'.$i.'_correct']));
                                    $isCorrect = ($flag === 'true' || $flag === '1' || $flag === 'yes' || $flag === 'y') ? 1 : 0;
                                    $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?,?,?)')
                                        ->execute([$qid, $ct, $isCorrect]);
                                }
                            }
                        }
                        $pdo->commit();
                        $success[] = 'Import completed: ' . $createdTests . ' test(s), ' . $createdQuestions . ' question(s).';
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $errors[] = 'Import failed: ' . $e->getMessage();
                    }
                }
            } else {
                $errors[] = 'The Excel file appears to be empty.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-3xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Import Tests</h1>
    <a href="<?= base_url('admin/tests.php') ?>" class="text-sm text-primary-700 hover:underline">Back</a>
  </div>

  <?php if ($errors): ?>
    <ul class="mb-4 text-red-700 bg-red-50 border border-red-200 rounded p-3 text-sm list-disc list-inside">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if ($success): ?>
    <ul class="mb-4 text-green-700 bg-green-50 border border-green-200 rounded p-3 text-sm list-disc list-inside">
      <?php foreach ($success as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="bg-white border border-slate-200 rounded p-6">
    <form method="post" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Excel file (.xlsx)</label>
        <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="block w-full text-sm" required />
      </div>
      <div class="flex items-center gap-3">
        <button class="px-4 py-2 rounded bg-primary-600 text-white" type="submit">Import</button>
        <a class="text-sm text-primary-700 hover:underline" href="<?= base_url('admin/tests_import_sample.php') ?>">Download sample Excel</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
