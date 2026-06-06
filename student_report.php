<?php
require_once __DIR__ . '/config.php';
requireLogin();

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$pdo = db();

if (!$studentId) {
    header('Location: index.php');
    exit;
}

$studentStmt = $pdo->prepare('SELECT s.id, s.first_name, s.last_name, s.admission_number, s.stream_id, st.name AS stream_name FROM students s JOIN streams st ON s.stream_id = st.id WHERE s.id = ?');
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch();
if (!$student) {
    echo 'Student not found.';
    exit;
}

$streamSubjectsStmt = $pdo->prepare('SELECT sub.id, sub.name FROM subjects sub JOIN stream_subjects ss ON ss.subject_id = sub.id WHERE ss.stream_id = ? ORDER BY sub.name');
$streamSubjectsStmt->execute([$student['stream_id']]);
$streamSubjects = $streamSubjectsStmt->fetchAll();

$streamStudentsStmt = $pdo->prepare('SELECT id FROM students WHERE stream_id = ?');
$streamStudentsStmt->execute([$student['stream_id']]);
$streamStudents = $streamStudentsStmt->fetchAll(PDO::FETCH_COLUMN);

$studentScoresStmt = $pdo->prepare('SELECT sc.assessment_type, sc.score, sc.subject_id, sub.name AS subject_name FROM scores sc JOIN subjects sub ON sc.subject_id = sub.id WHERE sc.student_id = ? ORDER BY sub.name, sc.assessment_type');
$studentScoresStmt->execute([$studentId]);
$scores = $studentScoresStmt->fetchAll();
$totals = calculateTotals($scores);

$rankings = [];
foreach ($streamStudents as $streamStudentId) {
    $scoreStmt = $pdo->prepare('SELECT sc.score, sc.assessment_type, sc.subject_id FROM scores sc WHERE sc.student_id = ?');
    $scoreStmt->execute([$streamStudentId]);
    $agg = calculateTotals($scoreStmt->fetchAll());
    $rankings[] = ['student_id' => (int) $streamStudentId, 'total' => $agg['total']];
}
usort($rankings, fn($a, $b) => $b['total'] <=> $a['total']);
$position = 1;
foreach ($rankings as $entry) {
    if ($entry['student_id'] === $studentId) {
        break;
    }
    $position++;
}

$subjectPositions = [];
foreach ($streamSubjects as $subject) {
    $subjectScoresStmt = $pdo->prepare('SELECT s.id AS student_id, COALESCE(SUM(CASE WHEN sc.assessment_type = "Exam" THEN sc.score ELSE 0 END),0) + COALESCE(SUM(CASE WHEN sc.assessment_type = "CA" THEN sc.score ELSE 0 END),0) AS total_score FROM students s LEFT JOIN scores sc ON sc.student_id = s.id AND sc.subject_id = ? WHERE s.stream_id = ? GROUP BY s.id ORDER BY total_score DESC');
    $subjectScoresStmt->execute([$subject['id'], $student['stream_id']]);
    $rank = 1;
    $lastScore = null;
    foreach ($subjectScoresStmt->fetchAll() as $index => $row) {
        $scoreValue = (float) $row['total_score'];
        if ($index > 0 && $scoreValue !== $lastScore) {
            $rank = $index + 1;
        }
        $subjectPositions[$subject['id']][$row['student_id']] = $rank;
        $lastScore = $scoreValue;
    }
}

function groupScores(array $scores): array {
    $grouped = [];
    foreach ($scores as $score) {
        $subId = $score['subject_id'];
        if (!isset($grouped[$subId])) {
            $grouped[$subId] = ['subject_id' => $subId, 'subject_name' => $score['subject_name'], 'exam' => 0, 'ca' => 0];
        }
        if ($score['assessment_type'] === 'Exam') {
            $grouped[$subId]['exam'] += (float) $score['score'];
        } else {
            $grouped[$subId]['ca'] += (float) $score['score'];
        }
    }
    return $grouped;
}

$subjectRows = groupScores($scores);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ikonex Academy — Student Report</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    body{background:#eef5ff;padding:30px;}
    .report-card{max-width:900px;margin:0 auto;background:white;border-radius:18px;box-shadow:0 20px 50px rgba(34,60,80,.12);padding:28px;}
    .report-card header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px;}
    .report-card h1{margin:0;font-size:1.8rem;}
    .report-card .meta{color:#64748b;}
    .report-card table{width:100%;border-collapse:collapse;margin-top:18px;}
    .report-card td, .report-card th{padding:12px;border:1px solid #eef4fa;text-align:left;}
    .report-actions{display:flex;gap:12px;justify-content:flex-end;margin-bottom:20px;}
    .print-note{margin-top:16px;color:#475569;}
  </style>
</head>
<body>
  <div class="report-card">
    <div class="report-actions">
      <button class="btn primary" onclick="window.print()">Print / Save PDF</button>
      <a class="btn" href="dashboard.php">Back to Dashboard</a>
    </div>
    <header>
      <div>
        <h1>Student Report Card</h1>
        <div class="muted">Ikonex Academy</div>
      </div>
      <div>
        <div><strong>Date:</strong> <?php echo date('F d, Y'); ?></div>
        <div><strong>Student ID:</strong> <?php echo sanitize($student['id']); ?></div>
      </div>
    </header>

    <div class="grid" style="grid-template-columns:1fr 1fr; gap:18px; margin-bottom:20px;">
      <div>
        <h4>Student Information</h4>
        <p><strong>Name:</strong> <?php echo sanitize($student['first_name'] . ' ' . $student['last_name']); ?></p>
        <p><strong>Admission No:</strong> <?php echo sanitize($student['admission_number']); ?></p>
      </div>
      <div>
        <h4>Academic Details</h4>
        <p><strong>Stream:</strong> <?php echo sanitize($student['stream_name']); ?></p>
        <p><strong>Position:</strong> <?php echo $position; ?> of <?php echo count($rankings); ?></p>
      </div>
    </div>

    <h4>Subject Performance</h4>
    <table>
      <thead>
        <tr><th>Subject</th><th>Exam</th><th>CA</th><th>Total</th><th>Grade</th><th>Position</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subjectRows as $subject):
          $total = $subject['exam'] + $subject['ca'];
          $grade = getGrade($total);
          $positionBySubject = $subjectPositions[$subject['subject_id']][$student['id']] ?? '-';
        ?>
          <tr>
            <td><?php echo sanitize($subject['subject_name']); ?></td>
            <td><?php echo number_format($subject['exam'], 2); ?></td>
            <td><?php echo number_format($subject['ca'], 2); ?></td>
            <td><?php echo number_format($total, 2); ?></td>
            <td><?php echo sanitize($grade['grade']); ?></td>
            <td><?php echo sanitize($positionBySubject); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="grid" style="grid-template-columns:1fr 1fr; gap:18px; margin-top:22px;">
      <div class="card" style="padding:18px;">
        <strong>Total Marks</strong>
        <p style="font-size:1.7rem; margin:8px 0 0;"><?php echo number_format($totals['total'], 2); ?></p>
      </div>
      <div class="card" style="padding:18px;">
        <strong>Average Score</strong>
        <p style="font-size:1.7rem; margin:8px 0 0;"><?php echo number_format($totals['average'], 2); ?></p>
        <small><?php echo sanitize($totals['remark']); ?></small>
      </div>
    </div>

    <p class="print-note">Use the browser Print dialog to save this report as PDF.</p>
  </div>
</body>
</html>
