<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Проверка наличия ID резюме
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID резюме не указан']);
    exit;
}

$resumeId = (int)$_GET['id'];

// Получение данных резюме
$query = "SELECT r.*, e.name as experience_name, s.salary_range,
          GROUP_CONCAT(sk.name) as skills
          FROM resumes r
          LEFT JOIN experiences e ON r.experience_id = e.id
          LEFT JOIN salaries s ON r.salary_id = s.id
          LEFT JOIN resume_skills rs ON r.id = rs.resume_id
          LEFT JOIN skills sk ON rs.skill_id = sk.id
          WHERE r.id = ?
          GROUP BY r.id";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$resumeId]);
    $resume = $stmt->fetch();

    if (!$resume) {
        http_response_code(404);
        echo json_encode(['error' => 'Резюме не найдено']);
        exit;
    }

    // Анализ резюме
    $score = 0;
    $details = [];

    // Анализ опыта работы
    $experienceScore = 0;
    if ($resume['experience_id'] >= 4) { // 3 года и более
        $experienceScore = 100;
        $experienceComment = 'Отличный опыт работы: ' . $resume['experience_name'];
    } elseif ($resume['experience_id'] >= 2) { // 1-2 года
        $experienceScore = 70;
        $experienceComment = 'Достаточный опыт работы: ' . $resume['experience_name'];
    } else {
        $experienceScore = 40;
        $experienceComment = 'Недостаточный опыт работы: ' . $resume['experience_name'];
    }
    $details[] = [
        'criterion' => 'Опыт работы',
        'score' => $experienceScore,
        'comment' => $experienceComment
    ];
    $score += $experienceScore;

    // Анализ описания
    $descriptionScore = 0;
    $descriptionLength = strlen($resume['description']);
    if ($descriptionLength > 500) {
        $descriptionScore = 100;
        $descriptionComment = 'Отличное описание: ' . $descriptionLength . ' символов';
    } elseif ($descriptionLength > 300) {
        $descriptionScore = 80;
        $descriptionComment = 'Хорошее описание: ' . $descriptionLength . ' символов';
    } elseif ($descriptionLength > 200) {
        $descriptionScore = 60;
        $descriptionComment = 'Среднее описание: ' . $descriptionLength . ' символов';
    } elseif ($descriptionLength > 100) {
        $descriptionScore = 40;
        $descriptionComment = 'Краткое описание: ' . $descriptionLength . ' символов';
    } else {
        $descriptionScore = 20;
        $descriptionComment = 'Слишком краткое описание: ' . $descriptionLength . ' символов';
    }
    $details[] = [
        'criterion' => 'Описание',
        'score' => $descriptionScore,
        'comment' => $descriptionComment
    ];
    $score += $descriptionScore;

    // Анализ навыков
    $skillsScore = 0;
    $skills = explode(',', $resume['skills']);
    $skillsCount = count($skills);
    if ($skillsCount >= 5) {
        $skillsScore = 100;
        $skillsComment = 'Отличный набор навыков: ' . $skillsCount . ' навыков';
    } elseif ($skillsCount >= 3) {
        $skillsScore = 80;
        $skillsComment = 'Хороший набор навыков: ' . $skillsCount . ' навыков';
    } elseif ($skillsCount >= 2) {
        $skillsScore = 60;
        $skillsComment = 'Средний набор навыков: ' . $skillsCount . ' навыков';
    } else {
        $skillsScore = 30;
        $skillsComment = 'Недостаточное количество навыков: ' . $skillsCount . ' навыков';
    }
    $details[] = [
        'criterion' => 'Навыки',
        'score' => $skillsScore,
        'comment' => $skillsComment
    ];
    $score += $skillsScore;

    // Расчет итогового балла
    $totalScore = round($score / 3);

    // Форматирование даты
    $createdAt = new DateTime($resume['created_at']);
    $formattedDate = $createdAt->format('d.m.Y H:i');

    $response = [
        'score' => $totalScore,
        'details' => $details,
        'resume_info' => [
            'title' => $resume['title'],
            'experience' => $resume['experience_name'],
            'salary' => $resume['salary_range'],
            'created_at' => $formattedDate,
            'visibility_duration' => $resume['visibility_duration'],
            'description' => $resume['description'],
            'skills' => $resume['skills']
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при анализе резюме: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Произошла ошибка: ' . $e->getMessage()]);
} 