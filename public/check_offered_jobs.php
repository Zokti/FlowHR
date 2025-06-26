<?php
require __DIR__ . '/../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

// Получение ID резюме
$resumeId = isset($_GET['resume_id']) ? (int)$_GET['resume_id'] : 0;

if (!$resumeId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Не указан ID резюме']);
    exit;
}

try {
    // Получение списка предложенных вакансий для данного резюме
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.job_id
        FROM applications a
        WHERE a.entity_type = 'resume'
        AND a.entity_id = ?
        AND a.status = 'pending'
    ");
    $stmt->execute([$resumeId]);
    $offeredJobs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode(['offered_jobs' => $offeredJobs]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 