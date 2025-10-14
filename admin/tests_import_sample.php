<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$headers = [
  'test_title','test_description','test_category','test_difficulty','test_time_limit_minutes',
  'question_text','question_type','question_points',
  'choice_1','choice_1_correct','choice_2','choice_2_correct','choice_3','choice_3_correct','choice_4','choice_4_correct',
  'correct_text_answer'
];

$rows = [
  ['Basic Excel Test','Basics of Excel operations','Excel','beginner','20','What is the shortcut to copy?','single','1','Ctrl+C','true','Ctrl+X','false','Ctrl+V','false','Ctrl+Z','false',''],
  ['Basic Excel Test','Basics of Excel operations','Excel','beginner','20','Select all cells shortcut?','single','1','Ctrl+A','true','Ctrl+S','false','Ctrl+L','false','Ctrl+E','false',''],
  ['Basic Excel Test','Basics of Excel operations','Excel','beginner','20','Which keys insert current date?','multiple','1','Ctrl+; (semicolon)','true','Ctrl+Shift+: (colon)','true','Ctrl+D','false','Ctrl+R','false',''],
  ['Basic Excel Test','Basics of Excel operations','Excel','beginner','20','Explain difference between workbook and worksheet.','text','1','','','','','','','','','A workbook contains one or more worksheets']
];

function stream_xlsx(array $headers, array $rows, string $filename = 'sample_tests.xlsx'): void {
  if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo 'ZipArchive not enabled on server.';
    return;
  }
  $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
  $zip = new ZipArchive();
  $zip->open($tmp, ZipArchive::OVERWRITE);

  // [Content_Types].xml
  $zip->addFromString('[Content_Types].xml',
    '<?xml version="1.0" encoding="UTF-8"?>'
    .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    .'<Default Extension="xml" ContentType="application/xml"/>'
    .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
    .'</Types>'
  );

  // _rels/.rels
  $zip->addFromString('_rels/.rels',
    '<?xml version="1.0" encoding="UTF-8"?>'
    .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    .'</Relationships>'
  );

  // xl/workbook.xml
  $zip->addFromString('xl/workbook.xml',
    '<?xml version="1.0" encoding="UTF-8"?>'
    .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
    .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    .'<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
    .'</workbook>'
  );

  // xl/_rels/workbook.xml.rels
  $zip->addFromString('xl/_rels/workbook.xml.rels',
    '<?xml version="1.0" encoding="UTF-8"?>'
    .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
    .'</Relationships>'
  );

  // Build shared strings and sheet
  $all = array_merge([$headers], $rows);
  $sMap = [];$sArr = [];$sid = 0;
  foreach ($all as $r) {
    foreach ($r as $v) {
      $v = (string)$v;
      if (!isset($sMap[$v])) { $sMap[$v] = $sid++; $sArr[] = $v; }
    }
  }
  $sharedXml = '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sArr).'" uniqueCount="'.count($sArr).'">';
  foreach ($sArr as $s) {
    $sharedXml .= '<si><t>'.htmlspecialchars($s).'</t></si>';
  }
  $sharedXml .= '</sst>';
  $zip->addFromString('xl/sharedStrings.xml', $sharedXml);

  // Sheet1
  $sheet = '<?xml version="1.0" encoding="UTF-8"?>'
    .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
  $rowIndex = 1;
  foreach ($all as $r) {
    $sheet .= '<row r="'.$rowIndex.'">';
    $col = 0;
    foreach ($r as $v) {
      $colRef = chr(65 + $col) . $rowIndex; // supports up to 26 columns; we have 17
      $si = $sMap[(string)$v];
      $sheet .= '<c r="'.$colRef.'" t="s"><v>'.$si.'</v></c>';
      $col++;
    }
    $sheet .= '</row>';
    $rowIndex++;
  }
  $sheet .= '</sheetData></worksheet>';
  $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

  $zip->close();

  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="sample_tests.xlsx"');
  readfile($tmp);
  @unlink($tmp);
}

stream_xlsx($headers, $rows);
