<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, name FROM streams ORDER BY name');
    $streams = $stmt->fetchAll();
    jsonResponse(['data' => $streams]);
}

$input = getJsonRequest();
$name = trim($input['name'] ?? '');

if ($method === 'POST') {
    if ($name === '') {
        jsonResponse(['error' => 'Stream name is required.'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO streams (name) VALUES (?)');
    $stmt->execute([$name]);

    jsonResponse(['data' => ['id' => $pdo->lastInsertId(), 'name' => $name]], 201);
}

if ($method === 'PUT') {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if (!$id || $name === '') {
        jsonResponse(['error' => 'Stream id and name are required.'], 422);
    }
    $stmt = $pdo->prepare('UPDATE streams SET name = ? WHERE id = ?');
    $stmt->execute([$name, $id]);
    jsonResponse(['data' => ['id' => $id, 'name' => $name]]);
}

if ($method === 'DELETE') {
    $data = getJsonRequest();
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    if (!$id) {
        jsonResponse(['error' => 'Stream id is required for delete.'], 422);
    }
    $stmt = $pdo->prepare('DELETE FROM streams WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['data' => ['id' => $id]]);
}

jsonResponse(['error' => 'Unsupported method'], 405);
