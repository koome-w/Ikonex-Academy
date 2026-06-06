<?php
require_once __DIR__ . '/config.php';
requireLogin();

$streamId = isset($_GET['stream_id']) ? (int) $_GET['stream_id'] : 0;
$pdo = db();

if (!$streamId) {
    header('Location: index.php');
    exit;
}

$streamStmt = $pdo->prepare('SELECT id, name FROM streams WHERE id = ?');
$streamStmt->execute([$streamId]);
$stream = $streamStmt->fetch();
if (!$stream) {
    echo 'Stream not found.';
    exit;
}

$studentsStmt = $pdo->prepare('SELECT id, first_name, last_name, admission_number FROM students WHERE stream_id = ? ORDER BY last_name, first_name');
$studentsStmt->execute([$streamId]);
$students = $studentsStmt->fetchAll();

$subjectsStmt = $pdo->prepare('SELECT sub.id, sub.name FROM subjects sub JOIN stream_subjects ss ON ss.subject_id = sub.id WHERE ss.stream_id = ? ORDER BY sub.name');
$subjectsStmt->execute([$streamId]);
$subjects = $subjectsStmt->fetchAll();

$scoreStmt = $pdo->prepare('SELECT sc.student_id, sc.subject_id, sc.assessment_type, sc.score FROM scores sc JOIN students st ON sc.student_id = st.id WHERE st.stream_id = ?');
$scoreStmt->execute([$streamId]);
$scores = $scoreStmt->fetchAll();

$studentMap = [];
foreach ($students as $student) {
    $studentMap[$student['id']] = [
        'student' => $student,
        'subjects' => [],
        'total' => 0,
        'average' => 0,
        'grade' => 'F',
        'remark' => 'Fail'
    ];
}

foreach ($scores as $score) {
    $id = $score['student_id'];
    if (!isset($studentMap[$id])) {
        continue;
    }
    if (!isset($studentMap[$id]['subjects'][$score['subject_id']])) {
        $studentMap[$id]['subjects'][$score['subject_id']] = ['exam' => 0, 'ca' => 0, 'total' => 0];
    }
    if ($score['assessment_type'] === 'Exam') {
        $studentMap[$id]['subjects'][$score['subject_id']]['exam'] += (float) $score['score'];
    } else {
        $studentMap[$id]['subjects'][$score['subject_id']]['ca'] += (float) $score['score'];
    }
    $studentMap[$id]['subjects'][$score['subject_id']]['total'] = $studentMap[$id]['subjects'][$score['subject_id']]['exam'] + $studentMap[$id]['subjects'][$score['subject_id']]['ca'];
}

foreach ($studentMap as &$entry) {
    $subjectCount = 0;
    $total = 0;
    foreach ($subjects as $subject) {
        $subjectData = $entry['subjects'][$subject['id']] ?? ['exam' => 0, 'ca' => 0, 'total' => 0];
        $total += $subjectData['total'];
        if ($subjectData['total'] > 0) {
            $subjectCount++;
        }
        $entry['subjects'][$subject['id']] = $subjectData;
    }
    $entry['total'] = round($total, 2);
    $entry['average'] = $subjectCount ? round($total / $subjectCount, 2) : 0.00;
    $gradeInfo = getGrade($entry['average']);
    $entry['grade'] = $gradeInfo['grade'];
    $entry['remark'] = $gradeInfo['remark'];
}
unset($entry);

$reportRows = array_values($studentMap);
usort($reportRows, fn($a, $b) => $b['total'] <=> $a['total']);

$subjectPositions = [];
foreach ($subjects as $subject) {
    $ranking = [];
    foreach ($reportRows as $item) {
        $ranking[] = [
            'student_id' => $item['student']['id'],
            'score' => $item['subjects'][$subject['id']]['total'] ?? 0
        ];
    }
    usort($ranking, fn($a, $b) => $b['score'] <=> $a['score']);
    $position = 1;
    $lastScore = null;
    foreach ($ranking as $index => $record) {
        if ($index && $record['score'] !== $lastScore) {
            $position = $index + 1;
        }
        $subjectPositions[$subject['id']][$record['student_id']] = $position;
        $lastScore = $record['score'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ikonex Academy — Class Report</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    body{background:#eef5ff;padding:30px;}
    .report-card{max-width:1000px;margin:0 auto;background:white;border-radius:18px;box-shadow:0 20px 50px rgba(34,60,80,.12);padding:28px;}
    .report-card header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px;}
    .report-card h1{margin:0;font-size:1.8rem;}
    .report-card table{width:100%;border-collapse:collapse;margin-top:18px;}
    .report-card td, .report-card th{padding:12px;border:1px solid #eef4fa;text-align:left;}
    .report-actions{display:flex;gap:12px;justify-content:flex-end;margin-bottom:20px;}
    .section-block{margin-top:28px;padding:18px;border-radius:16px;background:#f8fbff;}
  </style>
</head>
<body>
  <div class="report-card">
    <div class="report-actions">
      <button class="btn primary" onclick="window.print()">Print / Save PDF</button>
      <a class="btn" href="index.php">Back to Dashboard</a>
    </div>
    <header>
      <div>
        <h1>Class Performance Report</h1>
        <div class="muted">Stream: <?php echo sanitize($stream['name']); ?></div>
      </div>
      <div>
        <div><strong>Date:</strong> <?php echo date('F d, Y'); ?></div>
        <div><strong>Students:</strong> <?php echo count($reportRows); ?></div>
      </div>
    </header>

    <table>
      <thead>
        <tr><th>Position</th><th>Name</th><th>Admission No</th><th>Total</th><th>Average</th><th>Grade</th></tr>
      </thead>
      <tbody>
        <?php foreach ($reportRows as $index => $row): ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo sanitize($row['student']['first_name'] . ' ' . $row['student']['last_name']); ?></td>
            <td><?php echo sanitize($row['student']['admission_number']); ?></td>
            <td><?php echo number_format($row['total'], 2); ?></td>
            <td><?php echo number_format($row['average'], 2); ?></td>
            <td><?php echo sanitize($row['grade']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="section-block">
      <h3>Subject positions</h3>
      <?php if (count($subjects) === 0): ?>
        <p class="muted">No subjects assigned to this stream yet.</p>
      <?php else: ?>
        <?php foreach ($subjects as $subject): ?>
          <div style="margin-bottom:18px;">
            <h4><?php echo sanitize($subject['name']); ?></h4>
            <table>
              <thead>
                <tr><th>Position</th><th>Student</th><th>Total</th></tr>
              </thead>
              <tbody>
                <?php
                  $positionList = [];
                  foreach ($reportRows as $studentRow) {
                      $positionList[] = [
                          'student' => $studentRow['student'],
                          'score' => $studentRow['subjects'][$subject['id']]['total'] ?? 0,
                          'position' => $subjectPositions[$subject['id']][$studentRow['student']['id']] ?? 0
                      ];
                  }
                  usort($positionList, fn($a, $b) => $a['position'] <=> $b['position'] ?: $b['score'] <=> $a['score']);
                ?>
                <?php foreach ($positionList as $entry): ?>
                  <tr>
                    <td><?php echo $entry['position']; ?></td>
                    <td><?php echo sanitize($entry['student']['first_name'] . ' ' . $entry['student']['last_name']); ?></td>
                    <td><?php echo number_format($entry['score'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
