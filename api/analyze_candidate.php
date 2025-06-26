<?php
require_once '../includes/config.php';

// Проверка наличия ID кандидата
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID кандидата не указан']);
    exit;
}

$candidateId = (int)$_GET['id'];

// Получение данных кандидата
$query = "SELECT u.*, 
          COUNT(DISTINCT r.id) as total_resumes,
          COUNT(DISTINCT a.id) as total_applications,
          GROUP_CONCAT(DISTINCT s.name) as all_skills
          FROM users u
          LEFT JOIN resumes r ON u.id = r.user_id
          LEFT JOIN applications a ON u.id = a.candidate_id
          LEFT JOIN resume_skills rs ON r.id = rs.resume_id
          LEFT JOIN skills s ON rs.skill_id = s.id
          WHERE u.id = ?
          GROUP BY u.id";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$candidateId]);
    $candidate = $stmt->fetch();

    if (!$candidate) {
        http_response_code(404);
        echo json_encode(['error' => 'Кандидат не найден']);
        exit;
    }

    // Анализ кандидата
    $score = 0;
    $details = [];

    // Анализ заполненности профиля
    $profileScore = $candidate['profile_completion'];
    $details[] = [
        'criterion' => 'Заполненность профиля',
        'score' => $profileScore,
        'comment' => 'Процент заполнения профиля кандидата'
    ];
    $score += $profileScore;

    // Анализ активности
    $activityScore = 0;
    if ($candidate['total_resumes'] > 0) {
        $activityScore = min(($candidate['total_resumes'] * 20), 100);
    }
    $details[] = [
        'criterion' => 'Активность',
        'score' => $activityScore,
        'comment' => 'Количество созданных резюме: ' . $candidate['total_resumes']
    ];
    $score += $activityScore;

    // Анализ опыта
    $experienceScore = 0;
    if ($candidate['age'] >= 18 && $candidate['age'] <= 65) {
        $experienceScore = 100;
    } elseif ($candidate['age'] > 65) {
        $experienceScore = 50;
    }
    $details[] = [
        'criterion' => 'Возраст',
        'score' => $experienceScore,
        'comment' => 'Возраст кандидата: ' . $candidate['age']
    ];
    $score += $experienceScore;

    // Расчет итогового балла
    $totalScore = round($score / 3);

    echo json_encode([
        'score' => $totalScore,
        'details' => $details,
        'candidate_info' => [
            'name' => $candidate['name'],
            'email' => $candidate['email'],
            'total_resumes' => $candidate['total_resumes'],
            'total_applications' => $candidate['total_applications'],
            'skills' => $candidate['all_skills']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при анализе кандидата']);
    error_log($e->getMessage());
} 