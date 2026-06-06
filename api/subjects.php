<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $streamFilter = isset($_GET['stream_id']) ? (int) $_GET['stream_id'] : 0;
    $sql = 'SELECT sub.id, sub.name, GROUP_CONCAT(ss.stream_id) AS stream_ids FROM subjects sub LEFT JOIN stream_subjects ss ON ss.subject_id = sub.id';
    if ($streamFilter) {
        $sql .= ' WHERE sub.id IN (SELECT subject_id FROM stream_subjects WHERE stream_id = ?)';
    }
    $sql .= ' GROUP BY sub.id ORDER BY sub.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($streamFilter ? [$streamFilter] : []);
    $items = array_map(function($row) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'stream_ids' => $row['stream_ids'] ? array_map('intval', explode(',', $row['stream_ids'])) : []
        ];
    }, $stmt->fetchAll());
    jsonResponse(['data' => $items]);
}

$input = getJsonRequest();
$name = trim($input['name'] ?? '');
$streamIds = isset($input['stream_ids']) && is_array($input['stream_ids']) ? array_map('intval', $input['stream_ids']) : [];

if ($method === 'POST') {
    if ($name === '') {
        jsonResponse(['error' => 'Subject name is required.'], 422);
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO subjects (name) VALUES (?)');
    $stmt->execute([$name]);
    $subjectId = $pdo->lastInsertId();

    $stmtAssign = $pdo->prepare('INSERT IGNORE INTO stream_subjects (stream_id, subject_id) VALUES (?, ?)');
    foreach ($streamIds as $streamId) {
        if ($streamId) {
            $stmtAssign->execute([$streamId, $subjectId]);
        }
    }
    $pdo->commit();
    jsonResponse(['data' => ['id' => $subjectId, 'name' => $name, 'stream_ids' => $streamIds]], 201);
}

if ($method === 'PUT') {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if (!$id || $name === '') {
        jsonResponse(['error' => 'Subject id and name are required.'], 422);
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE subjects SET name = ? WHERE id = ?');
    $stmt->execute([$name, $id]);
    $pdo->prepare('DELETE FROM stream_subjects WHERE subject_id = ?')->execute([$id]);
    $stmtAssign = $pdo->prepare('INSERT IGNORE INTO stream_subjects (stream_id, subject_id) VALUES (?, ?)');
    foreach ($streamIds as $streamId) {
        if ($streamId) {
            $stmtAssign->execute([$streamId, $id]);
        }
    }
    $pdo->commit();
    jsonResponse(['data' => ['id' => $id, 'name' => $name, 'stream_ids' => $streamIds]]);
}

if ($method === 'DELETE') {
    $data = getJsonRequest();
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    if (!$id) {
        jsonResponse(['error' => 'Subject id is required for delete.'], 422);
    }
    $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['data' => ['id' => $id]]);
}

jsonResponse(['error' => 'Unsupported method'], 405);
