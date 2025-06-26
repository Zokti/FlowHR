<?php
require_once '../../includes/config.php';
header('Content-Type: text/html; charset=utf-8');

$resume_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$resume_id) {
    echo '<div class="text-danger">Некорректный ID резюме</div>';
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.*, u.name, u.email, s.salary_range, e.name as experience_name
    FROM resumes r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN salaries s ON r.salary_id = s.id
    LEFT JOIN experiences e ON r.experience_id = e.id
    WHERE r.id = ?
");
$stmt->execute([$resume_id]);
$resume = $stmt->fetch();

if (!$resume) {
    echo '<div class="text-danger">Резюме не найдено</div>';
    exit;
}

// Получаем навыки
$stmt = $pdo->prepare("SELECT s.name FROM resume_skills rs JOIN skills s ON rs.skill_id = s.id WHERE rs.resume_id = ?");
$stmt->execute([$resume_id]);
$skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<div style="padding:24px 18px 18px 18px; min-width:320px; max-width:480px; margin:0 auto;">
    <h3 style="margin-bottom:18px; color:#FF6F61; font-weight:700; font-size:1.35rem; text-align:center;">
        <i class="fas fa-user-tie"></i> <?= htmlspecialchars($resume['title']) ?>
    </h3>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-user" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Кандидат:</b>&nbsp;<?= htmlspecialchars($resume['name']) ?> (<?= htmlspecialchars($resume['email']) ?>)</div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-briefcase" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Опыт:</b>&nbsp;<?= htmlspecialchars($resume['experience_name']) ?: 'Не указан' ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-money-bill-wave" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Желаемая зарплата:</b>&nbsp;<?= htmlspecialchars($resume['salary_range']) ?: 'Не указана' ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-cogs" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Навыки:</b>&nbsp;<?= $skills ? implode(', ', array_map('htmlspecialchars', $skills)) : 'Нет' ?></div>
    <div style="margin-bottom:10px; display:flex; align-items:center;"><i class="fas fa-calendar-alt" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Дата создания:</b>&nbsp;<?= date('d.m.Y', strtotime($resume['created_at'])) ?></div>
    <div style="margin-top:18px; background:#FFF9F7; border-radius:12px; padding:14px; border:1px solid #FFE0DB;">
        <div style="display:flex; align-items:center; margin-bottom:6px;"><i class="fas fa-align-left" style="color:#FF6F61; width:22px;"></i>&nbsp;<b>Описание:</b></div>
        <span style="color:#444; display:block; margin-top:2px; line-height:1.6;"><?= nl2br(htmlspecialchars($resume['description'])) ?></span>
    </div>
</div> 