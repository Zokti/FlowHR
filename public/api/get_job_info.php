<?php
require_once '../../includes/config.php';
header('Content-Type: text/html; charset=utf-8');

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$job_id) {
    echo '<div class="text-danger">Некорректный ID вакансии</div>';
    exit;
}

$stmt = $pdo->prepare("
    SELECT j.*, u.name as hr_name, s.salary_range, e.name as experience_name
    FROM jobs j
    JOIN users u ON j.hr_id = u.id
    LEFT JOIN salaries s ON j.salary_id = s.id
    LEFT JOIN experiences e ON j.experience_id = e.id
    WHERE j.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    echo '<div class="text-danger">Вакансия не найдена</div>';
    exit;
}

// Получаем навыки
$stmt = $pdo->prepare("SELECT s.name FROM job_skills js JOIN skills s ON js.skill_id = s.id WHERE js.job_id = ?");
$stmt->execute([$job_id]);
$skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<div style="padding:24px 18px 18px 18px; min-width:320px; max-width:480px; margin:0 auto;">
    <h3 style="margin-bottom:18px; color:#FF6F61; font-weight:700; font-size:1.35rem; text-align:center;">
        <i class="fas fa-briefcase"></i> <?= htmlspecialchars($job['title']) ?>
    </h3>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-user-tie" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>HR-специалист:</b>&nbsp;<?= htmlspecialchars($job['hr_name']) ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-briefcase" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Опыт:</b>&nbsp;<?= htmlspecialchars($job['experience_name']) ?: 'Не указан' ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-money-bill-wave" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Зарплата:</b>&nbsp;<?= htmlspecialchars($job['salary_range']) ?: 'Не указана' ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-cogs" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Навыки:</b>&nbsp;<?= $skills ? implode(', ', array_map('htmlspecialchars', $skills)) : 'Нет' ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-calendar-alt" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Дата создания:</b>&nbsp;<?= date('d.m.Y', strtotime($job['created_at'])) ?></div>
    <div style="margin-top:18px; background:#F0F9FF; border-radius:12px; padding:14px; border:1px solid #BFE6FF;">
        <div style="display:flex; align-items:center; margin-bottom:6px;"><i class="fas fa-align-left" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Описание:</b></div>
        <span style="color:#444; display:block; margin-top:2px; line-height:1.6;"><?= nl2br(htmlspecialchars($job['description'])) ?></span>
    </div>
</div> 