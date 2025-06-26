<?php
session_start();
require '../includes/config.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing application_id']);
    exit;
}

try {
    // Получаем текущий статус заявки
    $stmt = $pdo->prepare("
        SELECT status 
        FROM applications 
        WHERE id = ? AND (hr_id = ? OR candidate_id = ?)
    ");
    $stmt->execute([$application_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $application = $stmt->fetch();

    if (!$application) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'status' => $application['status']
    ]);
} catch(Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 