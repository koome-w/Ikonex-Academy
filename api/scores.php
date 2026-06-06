<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = 'SELECT sc.id, sc.student_id, sc.subject_id, sc.assessment_type, sc.score, sc.recorded_at, CONCAT(st.first_name, " ", st.last_name) AS student_name, sub.name AS subject_name, st.stream_id FROM scores sc JOIN students st ON sc.student_id = st.id JOIN subjects sub ON sc.subject_id = sub.id';
    $params = [];
    $conditions = [];
    if (isset($_GET['stream_id']) && $_GET['stream_id'] !== '') {
        $conditions[] = 'st.stream_id = ?';
        $params[] = (int) $_GET['stream_id'];
    }
    if (isset($_GET['subject_id']) && $_GET['subject_id'] !== '') {
        $conditions[] = 'sc.subject_id = ?';
        $params[] = (int) $_GET['subject_id'];
    }
    if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
        $conditions[] = 'sc.student_id = ?';
        $params[] = (int) $_GET['student_id'];
    }
    if ($conditions) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $query .= ' ORDER BY sc.recorded_at DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    jsonResponse(['data' => $stmt->fetchAll()]);
}

$input = getJsonRequest();
$studentId = isset($input['student_id']) ? (int) $input['student_id'] : 0;
$subjectId = isset($input['subject_id']) ? (int) $input['subject_id'] : 0;
$type = trim($input['assessment_type'] ?? '');
$score = isset($input['score']) ? (float) $input['score'] : null;

if ($method === 'POST') {
    if (!$studentId || !$subjectId || $type === '' || $score === null) {
        jsonResponse(['error' => 'Student, subject, assessment type, and score are required.'], 422);
    }
    if ($score < 0 || $score > 100) {
        jsonResponse(['error' => 'Score must be between 0 and 100.'], 422);
    }

    $existing = $pdo->prepare('SELECT id FROM scores WHERE student_id = ? AND subject_id = ? AND assessment_type = ?');
    $existing->execute([$studentId, $subjectId, $type]);
    if ($existing->fetch()) {
        jsonResponse(['error' => 'Duplicate score detected. Use update to change score.'], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO scores (student_id, subject_id, assessment_type, score) VALUES (?, ?, ?, ?)');
    $stmt->execute([$studentId, $subjectId, $type, $score]);
    jsonResponse(['data' => ['id' => $pdo->lastInsertId(), 'student_id' => $studentId, 'subject_id' => $subjectId, 'assessment_type' => $type, 'score' => $score]], 201);
}

if ($method === 'PUT') {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if (!$id || $score === null) {
        jsonResponse(['error' => 'Score id and value are required.'], 422);
    }
    if ($score < 0 || $score > 100) {
        jsonResponse(['error' => 'Score must be between 0 and 100.'], 422);
    }
    $stmt = $pdo->prepare('UPDATE scores SET score = ? WHERE id = ?');
    $stmt->execute([$score, $id]);
    jsonResponse(['data' => ['id' => $id, 'score' => $score]]);
}

if ($method === 'DELETE') {
    $data = getJsonRequest();
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    if (!$id) {
        jsonResponse(['error' => 'Score id is required for delete.'], 422);
    }
    $stmt = $pdo->prepare('DELETE FROM scores WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['data' => ['id' => $id]]);
}

jsonResponse(['error' => 'Unsupported method'], 405);
