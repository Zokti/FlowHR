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
$last_id = $_GET['last_id'] ?? 0;

if (!$application_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing application_id']);
    exit;
}

try {
    // Проверяем доступ к чату
    $stmt = $pdo->prepare("
        SELECT id 
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

    // Получаем новые сообщения
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            u.name as sender_name,
            u.avatar as sender_avatar
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.application_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$application_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем даты для отображения
    foreach ($messages as &$message) {
        $message['created_at'] = date('d.m.Y H:i', strtotime($message['created_at']));
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch(Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 