<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pdo = db();
$type = $_GET['type'] ?? 'student';

function calculateStudentReport(PDO $pdo, int $studentId) {
    $studentStmt = $pdo->prepare('SELECT s.id, s.first_name, s.last_name, s.admission_number, s.stream_id, st.name AS stream_name FROM students s JOIN streams st ON s.stream_id = st.id WHERE s.id = ?');
    $studentStmt->execute([$studentId]);
    $student = $studentStmt->fetch();
    if (!$student) {
        return null;
    }

    $scoreStmt = $pdo->prepare('SELECT sc.id, sc.assessment_type, sc.score, sub.id AS subject_id, sub.name AS subject_name FROM scores sc JOIN subjects sub ON sc.subject_id = sub.id WHERE sc.student_id = ? ORDER BY sub.name, sc.assessment_type');
    $scoreStmt->execute([$studentId]);
    $scores = $scoreStmt->fetchAll();
    $totals = calculateTotals($scores);

    $streamStudentsStmt = $pdo->prepare('SELECT id FROM students WHERE stream_id = ?');
    $streamStudentsStmt->execute([$student['stream_id']]);
    $students = $streamStudentsStmt->fetchAll();

    $rankings = [];
    foreach ($students as $row) {
        $subjectStmt = $pdo->prepare('SELECT sc.score, sc.assessment_type, sub.id AS subject_id, sub.name AS subject_name FROM scores sc JOIN subjects sub ON sc.subject_id = sub.id WHERE sc.student_id = ?');
        $subjectStmt->execute([(int) $row['id']]);
        $subjectScores = $subjectStmt->fetchAll();
        $agg = calculateTotals($subjectScores);
        $rankings[] = ['student_id' => (int) $row['id'], 'total' => $agg['total']];
    }

    usort($rankings, fn($a, $b) => $b['total'] <=> $a['total']);
    $position = 1;
    foreach ($rankings as $entry) {
        if ($entry['student_id'] === $studentId) {
            break;
        }
        $position++;
    }

    return [
        'student' => $student,
        'scores' => $scores,
        'totals' => $totals,
        'position' => $position,
        'class_size' => count($rankings)
    ];
}

function calculateClassReport(PDO $pdo, int $streamId) {
    $studentsStmt = $pdo->prepare('SELECT id, first_name, last_name, admission_number FROM students WHERE stream_id = ? ORDER BY last_name, first_name');
    $studentsStmt->execute([$streamId]);
    $students = $studentsStmt->fetchAll();

    $report = [];
    foreach ($students as $student) {
        $scoreStmt = $pdo->prepare('SELECT sc.score, sc.assessment_type, sub.id AS subject_id, sub.name AS subject_name FROM scores sc JOIN subjects sub ON sc.subject_id = sub.id WHERE sc.student_id = ?');
        $scoreStmt->execute([$student['id']]);
        $aggregated = calculateTotals($scoreStmt->fetchAll());
        $report[] = [
            'student' => $student,
            'total' => $aggregated['total'],
            'average' => $aggregated['average'],
            'grade' => $aggregated['grade'],
            'remark' => $aggregated['remark']
        ];
    }

    usort($report, fn($a, $b) => $b['total'] <=> $a['total']);
    foreach ($report as $index => &$row) {
        $row['position'] = $index + 1;
    }
    unset($row);

    return ['data' => $report];
}

if ($type === 'student') {
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    if (!$studentId) {
        jsonResponse(['error' => 'Student id is required.'], 422);
    }
    $report = calculateStudentReport($pdo, $studentId);
    if (!$report) {
        jsonResponse(['error' => 'Student not found.'], 404);
    }
    jsonResponse(['data' => $report]);
}

if ($type === 'class') {
    $streamId = isset($_GET['stream_id']) ? (int) $_GET['stream_id'] : 0;
    if (!$streamId) {
        jsonResponse(['error' => 'Stream id is required.'], 422);
    }
    $report = calculateClassReport($pdo, $streamId);
    jsonResponse(['data' => $report]);
}

jsonResponse(['error' => 'Unsupported report type.'], 405);
