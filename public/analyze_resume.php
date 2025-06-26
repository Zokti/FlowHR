<?php
require __DIR__ . '/../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);
$resume_id = $data['resume_id'] ?? null;

if (!$resume_id) {
    echo json_encode(['success' => false, 'message' => 'ID резюме не указан']);
    exit;
}

try {
    // Получение данных резюме
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.name,
            u.email,
            u.phone,
            u.city,
            u.age,
            u.profile_completion,
            s.salary_range,
            e.name as experience_name,
            GROUP_CONCAT(DISTINCT sk.name) as skills
        FROM resumes r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN salaries s ON r.salary_id = s.id
        LEFT JOIN experiences e ON r.experience_id = e.id
        LEFT JOIN resume_skills rs ON r.id = rs.resume_id
        LEFT JOIN skills sk ON rs.skill_id = sk.id
        WHERE r.id = ?
        GROUP BY r.id
    ");
    $stmt->execute([$resume_id]);
    $resume = $stmt->fetch();

    if (!$resume) {
        echo json_encode(['success' => false, 'message' => 'Резюме не найдено']);
        exit;
    }

    // Получение активных вакансий HR
    $stmt = $pdo->prepare("
        SELECT 
            j.*,
            s.salary_range,
            GROUP_CONCAT(DISTINCT sk.name) as required_skills
        FROM jobs j
        LEFT JOIN salaries s ON j.salary_id = s.id
        LEFT JOIN job_skills js ON j.id = js.job_id
        LEFT JOIN skills sk ON js.skill_id = sk.id
        WHERE j.hr_id = ? AND j.status = 'active'
        GROUP BY j.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $jobs = $stmt->fetchAll();

    // Анализ резюме
    $analysis = [
        'general_assessment' => '',
        'strengths' => [],
        'recommendations' => [],
        'job_matches' => []
    ];

    // Общая оценка
    $profile_completion = $resume['profile_completion'] ?? 0;
    $skills_count = !empty($resume['skills']) ? count(explode(',', $resume['skills'])) : 0;
    $description_length = strlen($resume['description'] ?? '');

    $analysis['general_assessment'] = "Профиль заполнен на {$profile_completion}%. ";
    if ($skills_count > 0) {
        $analysis['general_assessment'] .= "Указано {$skills_count} навыков. ";
    }
    if ($description_length > 0) {
        $analysis['general_assessment'] .= "Описание резюме " . 
            ($description_length > 500 ? "подробное" : "требует дополнения") . ". ";
    }

    // Сильные стороны
    if ($profile_completion >= 80) {
        $analysis['strengths'][] = "Полностью заполненный профиль";
    }
    if ($skills_count >= 5) {
        $analysis['strengths'][] = "Хороший набор профессиональных навыков";
    }
    if ($description_length > 500) {
        $analysis['strengths'][] = "Детальное описание опыта работы";
    }
    if (!empty($resume['experience_name'])) {
        $analysis['strengths'][] = "Указан опыт работы: {$resume['experience_name']}";
    }

    // Рекомендации
    if ($profile_completion < 80) {
        $analysis['recommendations'][] = "Рекомендуется заполнить профиль полностью";
    }
    if ($skills_count < 5) {
        $analysis['recommendations'][] = "Добавьте больше профессиональных навыков";
    }
    if ($description_length < 500) {
        $analysis['recommendations'][] = "Расширьте описание опыта работы";
    }
    if (empty($resume['experience_name'])) {
        $analysis['recommendations'][] = "Укажите опыт работы";
    }

    // Соответствие вакансиям
    $resume_skills = !empty($resume['skills']) ? explode(',', $resume['skills']) : [];
    foreach ($jobs as $job) {
        $job_skills = !empty($job['required_skills']) ? explode(',', $job['required_skills']) : [];
        $matching_skills = array_intersect($resume_skills, $job_skills);
        $match_percentage = !empty($job_skills) ? 
            round(count($matching_skills) / count($job_skills) * 100) : 0;
        
        if ($match_percentage >= 50) {
            $analysis['job_matches'][] = "{$job['title']} - соответствие {$match_percentage}%";
        }
    }

    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);

} catch(Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при анализе резюме: ' . $e->getMessage()
    ]);
} 