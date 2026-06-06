<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filters = [];
    $sql = 'SELECT s.id, s.first_name, s.last_name, s.admission_number, s.stream_id, st.name AS stream_name FROM students s JOIN streams st ON s.stream_id = st.id';
    if (isset($_GET['stream_id']) && $_GET['stream_id'] !== '') {
        $sql .= ' WHERE s.stream_id = ?';
        $filters[] = (int) $_GET['stream_id'];
    }
    $sql .= ' ORDER BY s.last_name, s.first_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filters);
    jsonResponse(['data' => $stmt->fetchAll()]);
}

$input = getJsonRequest();
$first = trim($input['first_name'] ?? '');
$last = trim($input['last_name'] ?? '');
$streamId = isset($input['stream_id']) ? (int) $input['stream_id'] : 0;
$adm = trim($input['admission_number'] ?? '');

if ($method === 'POST') {
    if ($first === '' || $last === '' || !$streamId) {
        jsonResponse(['error' => 'First name, last name and stream are required.'], 422);
    }
    $stmt = $pdo->prepare('INSERT INTO students (first_name, last_name, admission_number, stream_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$first, $last, $adm, $streamId]);
    jsonResponse(['data' => ['id' => $pdo->lastInsertId(), 'first_name' => $first, 'last_name' => $last, 'admission_number' => $adm, 'stream_id' => $streamId]] , 201);
}

if ($method === 'PUT') {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if (!$id || $first === '' || $last === '' || !$streamId) {
        jsonResponse(['error' => 'Student id, first name, last name and stream are required.'], 422);
    }
    $stmt = $pdo->prepare('UPDATE students SET first_name = ?, last_name = ?, admission_number = ?, stream_id = ? WHERE id = ?');
    $stmt->execute([$first, $last, $adm, $streamId, $id]);
    jsonResponse(['data' => ['id' => $id, 'first_name' => $first, 'last_name' => $last, 'admission_number' => $adm, 'stream_id' => $streamId]]);
}

if ($method === 'DELETE') {
    $data = getJsonRequest();
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    if (!$id) {
        jsonResponse(['error' => 'Student id is required for delete.'], 422);
    }
    $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['data' => ['id' => $id]]);
}

jsonResponse(['error' => 'Unsupported method'], 405);
