<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if (!isset($_GET['resume_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID резюме не указан']);
    exit;
}

$resumeId = (int)$_GET['resume_id'];

try {
    // Получаем данные резюме
    $resumeQuery = "SELECT r.*, GROUP_CONCAT(rs.skill_id) as resume_skills
                   FROM resumes r
                   LEFT JOIN resume_skills rs ON r.id = rs.resume_id
                   WHERE r.id = ?
                   GROUP BY r.id";
    
    $stmt = $pdo->prepare($resumeQuery);
    $stmt->execute([$resumeId]);
    $resume = $stmt->fetch();

    if (!$resume) {
        http_response_code(404);
        echo json_encode(['error' => 'Резюме не найдено']);
        exit;
    }

    // Получаем все активные вакансии
    $jobsQuery = "SELECT j.*, e.name as experience_name, s.salary_range,
                  GROUP_CONCAT(DISTINCT a.id) as application_ids
                  FROM jobs j
                  LEFT JOIN experiences e ON j.experience_id = e.id
                  LEFT JOIN salaries s ON j.salary_id = s.id
                  LEFT JOIN applications a ON j.id = a.job_id AND a.entity_type = 'resume' AND a.entity_id = ?
                  WHERE j.status = 'active'
                  GROUP BY j.id";
    
    $stmt = $pdo->prepare($jobsQuery);
    $stmt->execute([$resumeId]);
    $jobs = $stmt->fetchAll();

    $suggestions = [];
    $resumeSkills = explode(',', $resume['resume_skills']);

    foreach ($jobs as $job) {
        $jobSkills = explode(',', $job['skill_ids']);
        $matchingSkills = array_intersect($resumeSkills, $jobSkills);
        $matchCount = count($matchingSkills);
        $totalSkills = count($jobSkills);
        
        // Определяем тип совпадения
        $matchType = 'none';
        if ($matchCount == $totalSkills && $resume['experience_id'] >= $job['experience_id']) {
            $matchType = 'full';
        } elseif ($matchCount > 0) {
            $matchType = 'partial';
        }

        // Проверяем, было ли уже предложение
        $isProposed = !empty($job['application_ids']);

        $suggestions[] = [
            'id' => $job['id'],
            'title' => $job['title'],
            'description' => $job['description'],
            'experience' => $job['experience_name'],
            'salary' => $job['salary_range'],
            'match_type' => $matchType,
            'matching_skills' => $matchCount,
            'total_skills' => $totalSkills,
            'is_proposed' => $isProposed
        ];
    }

    echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при получении предложений: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Произошла ошибка: ' . $e->getMessage()]);
} 