<?php

session_start();

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'ikonex_academy');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('GRADE_SCALE', serialize([
    ['grade' => 'A', 'min' => 70, 'max' => 100, 'remark' => 'Excellent'],
    ['grade' => 'B', 'min' => 60, 'max' => 69, 'remark' => 'Very Good'],
    ['grade' => 'C', 'min' => 50, 'max' => 59, 'remark' => 'Good'],
    ['grade' => 'D', 'min' => 40, 'max' => 49, 'remark' => 'Fair'],
    ['grade' => 'E', 'min' => 30, 'max' => 39, 'remark' => 'Weak'],
    ['grade' => 'F', 'min' => 0, 'max' => 29, 'remark' => 'Fail'],
]));

function db() {
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function jsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getJsonRequest() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function getGradeScale() {
    return unserialize(GRADE_SCALE, ['allowed_classes' => false]);
}

function getGrade($score) {
    foreach (getGradeScale() as $rule) {
        if ($score >= $rule['min'] && $score <= $rule['max']) {
            return ['grade' => $rule['grade'], 'remark' => $rule['remark']];
        }
    }
    return ['grade' => 'F', 'remark' => 'Fail'];
}

function calculateTotals(array $scores): array {
    $subjects = [];
    foreach ($scores as $score) {
        $subjectName = $score['name'] ?? $score['subject_name'] ?? 'Unknown Subject';
        $key = $score['subject_id'];
        if (!isset($subjects[$key])) {
            $subjects[$key] = [
                'subject_id' => $score['subject_id'],
                'name' => $subjectName,
                'exam' => 0,
                'ca' => 0,
                'total' => 0,
            ];
        }

        if ($score['assessment_type'] === 'Exam') {
            $subjects[$key]['exam'] += (float) $score['score'];
        } else {
            $subjects[$key]['ca'] += (float) $score['score'];
        }
        $subjects[$key]['total'] = $subjects[$key]['exam'] + $subjects[$key]['ca'];
    }

    $total = 0;
    foreach ($subjects as &$subject) {
        $total += $subject['total'];
        $subject['grade_info'] = getGrade($subject['total']);
    }
    unset($subject);

    $count = count($subjects);
    $average = $count ? round($total / $count, 2) : 0;
    $gradeInfo = getGrade($average);

    return [
        'subjects' => array_values($subjects),
        'total' => round($total, 2),
        'average' => $average,
        'grade' => $gradeInfo['grade'],
        'remark' => $gradeInfo['remark'],
    ];
}
