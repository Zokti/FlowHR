<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['job_id']) || !isset($data['resume_id']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Не все необходимые данные предоставлены']);
    exit;
}

$jobId = (int)$data['job_id'];
$resumeId = (int)$data['resume_id'];
$message = $data['message'];
$hrId = 1; // Временно хардкодим ID HR, позже нужно будет получать из сессии

try {
    // Проверяем, не было ли уже предложения
    $checkQuery = "SELECT id FROM applications 
                  WHERE job_id = ? AND entity_type = 'resume' AND entity_id = ?";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([$jobId, $resumeId]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Вакансия уже была предложена']);
        exit;
    }

    // Получаем ID кандидата из резюме
    $resumeQuery = "SELECT user_id FROM resumes WHERE id = ?";
    $stmt = $pdo->prepare($resumeQuery);
    $stmt->execute([$resumeId]);
    $resume = $stmt->fetch();

    if (!$resume) {
        http_response_code(404);
        echo json_encode(['error' => 'Резюме не найдено']);
        exit;
    }

    // Создаем предложение
    $insertQuery = "INSERT INTO applications (job_id, hr_id, candidate_id, entity_type, entity_id, message) 
                   VALUES (?, ?, ?, 'resume', ?, ?)";
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([$jobId, $hrId, $resume['user_id'], $resumeId, $message]);

    echo json_encode(['success' => true, 'message' => 'Вакансия успешно предложена']);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при предложении вакансии: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Произошла ошибка: ' . $e->getMessage()]);
} 