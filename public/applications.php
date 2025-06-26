<?php
session_start();
require '../includes/config.php';
require_once '../includes/header.php';

// Проверяем, авторизован ли пользователь и является ли он HR
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Обработка изменения статуса отклика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];

    $update_query = "UPDATE applications SET status = ? WHERE id = ? AND hr_id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$status, $application_id, $_SESSION['user_id']]);
}

try {
    // Получение списка вакансий HR для фильтра
    $jobs_stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE hr_id = ? ORDER BY title");
    $jobs_stmt->execute([$user_id]);
    $jobs = $jobs_stmt->fetchAll();

    // Получение откликов на вакансии с учетом фильтра
    $job_filter = isset($_GET['job_id']) ? $_GET['job_id'] : null;
    
    $query = "
        SELECT 
            a.id,
            a.entity_id,
            a.entity_type,
            a.candidate_id,
            a.status,
            a.message,
            a.created_at,
            j.title as job_title,
            u.name as applicant_name,
            u.email as applicant_email
        FROM applications a
        JOIN jobs j ON a.entity_id = j.id AND a.entity_type = 'vacancy'
        JOIN users u ON a.candidate_id = u.id
        WHERE j.hr_id = ?
    ";
    
    $params = [$user_id];
    
    if ($job_filter) {
        $query .= " AND j.id = ?";
        $params[] = $job_filter;
    }
    
    $query .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
} catch(Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отклики на вакансии</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Font Awesome -->
    <style>
        :root {
            --primary-color: #FF6F61;
            --primary-dark: #FF3B2F;
            --text-color: #2C3E50;
            --bg-color: #F8F9FA;
            --card-bg: #FFFFFF;
            --border-color: #E0E0E0;
            --success-color: #28a745;
            --warning-color: #FFC107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
            margin-left: 250px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: var(--text-color);
            text-align: center;
            margin: 40px 0;
            font-size: 36px;
            font-weight: bold;
        }

        h1 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .applications-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 100px auto;
            max-width: 600px;
            width: 90%;
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .empty-state p {
            color: var(--text-color);
            font-size: 1.2rem;
            margin: 0;
            line-height: 1.5;
        }

        .application-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .application-card h5 {
            color: var(--text-color);
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            font-weight: 600;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .application-card h5 i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .application-message {
            color: #6C757D;
            font-size: 1rem;
            margin: 15px 0;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1;
            padding: 15px;
            background: #F8F9FA;
            border-radius: 12px;
            border: 1px solid #E0E0E0;
        }

        .application-message i {
            color: var(--primary-color);
            margin-right: 8px;
        }

        .application-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .application-details .detail-item {
            display: flex;
            align-items: center;
            color: #6C757D;
        }

        .application-details .detail-item i {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .application-details .detail-item p {
            margin: 0;
            font-size: 0.95rem;
        }

        .application-details .detail-item strong {
            color: var(--text-color);
            font-weight: 600;
        }

        .card-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            gap: 6px;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .status-interview {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        .status-hired {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.2);
        }

        .btn-chat {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.2);
        }

        .btn-chat:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 111, 97, 0.3);
        }

        .btn-chat i {
            font-size: 1.2rem;
            color: white;
            opacity: 1;
            font-weight: 900;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
            width: 100%;
            flex-wrap: wrap;
        }

        .card-actions .btn {
            min-width: 120px;
            width: auto;
            flex: 1;
            max-width: 200px;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary i {
            font-size: 1.1rem;
            color: white;
            opacity: 1;
        }

        .status-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .status-modal.show {
            display: flex;
        }

        .status-modal-content {
            background: white;
            padding: 25px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .status-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .status-modal-title {
            font-size: 1.2rem;
            color: var(--text-color);
            margin: 0;
        }

        .status-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .status-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .status-option {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            color: var(--text-color);
        }

        .status-option:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .status-option i {
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .status-option.pending {
            background: #fff8e1;
            color: #ffc107;
        }

        .status-option.interview {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-option.hired {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-option.rejected {
            background: #ffebee;
            color: #dc3545;
        }

        .filter-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        }

        .filter-section .row {
            align-items: center;
            margin: 0;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .filter-section .col-md-6 {
            position: relative;
            padding: 0;
            width: 100%;
        }

        .filter-section form {
            width: 100%;
        }

        .filter-section select {
            width: 100%;
            padding: 18px 25px;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            font-size: 1.1rem;
            color: var(--text-color);
            background-color: white;
            transition: all 0.3s ease;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .filter-section select::-ms-expand {
            display: none;
        }

        .filter-section select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.1);
            outline: none;
            transform: translateY(-1px);
        }

        .filter-section select:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }

        .filter-section select option {
            padding: 15px 20px;
            font-size: 1.1rem;
            background-color: white;
            color: var(--text-color);
            border-bottom: 1px solid #f0f0f0;
        }

        .filter-section select option:first-child {
            font-weight: 600;
            color: var(--primary-color);
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
            background-color: #f8f9fa;
        }

        .filter-section select option:not(:first-child) {
            padding-left: 30px;
            position: relative;
        }

        .filter-section select option:not(:first-child)::before {
            content: '•';
            position: absolute;
            left: 15px;
            color: var(--primary-color);
            opacity: 0.5;
        }

        .filter-section select option:hover {
            background-color: #f8f9fa;
        }

        .filter-section .form-select {
            position: relative;
        }

        .filter-section .col-md-6::after {
            content: '🔍';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3rem;
            pointer-events: none;
            opacity: 0.5;
            z-index: 2;
        }

        .filter-section .col-md-6::before {
            content: '';
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 30px;
            background: #e9ecef;
            z-index: 2;
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            .container {
                padding: 10px;
            }

            h1 {
                font-size: 28px;
                margin: 20px 0;
            }

            .application-card {
                padding: 20px;
            }

            .card-actions {
                flex-direction: column;
                align-items: center;
            }

            .card-actions .btn {
                width: 100%;
                max-width: 250px;
            }

            .filter-section {
                padding: 20px;
                margin: 0 10px 20px 10px;
            }

            .filter-section select {
                padding: 15px 20px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i> Отклики на вакансии</h1>

        <div class="filter-section">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <select name="job_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Выберите вакансию для фильтрации</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?= $job['id'] ?>" <?= ($job_filter == $job['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="applications-list">
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <?php if ($job_filter): 
                        $job_title = '';
                        foreach ($jobs as $job) {
                            if ($job['id'] == $job_filter) {
                                $job_title = $job['title'];
                                break;
                            }
                        }
                    ?>
                        <p>По вакансии "<?= htmlspecialchars($job_title) ?>" пока нет откликов</p>
                    <?php else: ?>
                        <p>У вас пока нет откликов на вакансии</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <h5><i class="fas fa-briefcase"></i> <?= htmlspecialchars($application['job_title']) ?></h5>
                        
                        <div class="application-details">
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <p><strong>Кандидат:</strong> <?= htmlspecialchars($application['applicant_name']) ?></p>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <p><strong>Email:</strong> <?= htmlspecialchars($application['applicant_email']) ?></p>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <p><strong>Дата отклика:</strong> <?= date('d.m.Y H:i', strtotime($application['created_at'])) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($application['message'])): ?>
                            <div class="application-message">
                                <i class="fas fa-comment"></i> <?= htmlspecialchars($application['message']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="card-footer">
                            <div class="status-badge status-<?= $application['status'] ?>">
                                <i class="fas fa-circle"></i>
                                <?php
                                switch($application['status']) {
                                    case 'pending':
                                        echo 'На рассмотрении';
                                        break;
                                    case 'interview':
                                        echo 'Собеседование';
                                        break;
                                    case 'hired':
                                        echo 'Принят';
                                        break;
                                    case 'rejected':
                                        echo 'Отклонен';
                                        break;
                                }
                                ?>
                            </div>

                            <div class="card-actions">
                                <a href="messenger.php?user=<?= $application['candidate_id'] ?>" class="btn btn-chat">
                                    <i class="fas fa-paper-plane"></i> Написать
                                </a>
                                <button onclick="openStatusModal(<?= $application['id'] ?>, '<?= $application['status'] ?>')" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Статус
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Модальное окно для изменения статуса -->
    <div id="statusModal" class="status-modal">
        <div class="status-modal-content">
            <div class="status-modal-header">
                <h3 class="status-modal-title">Изменить статус</h3>
                <button class="status-modal-close" onclick="closeStatusModal()">&times;</button>
            </div>
            <form id="statusForm" method="POST">
                <input type="hidden" name="application_id" id="modalApplicationId">
                <input type="hidden" name="update_status" value="1">
                <div class="status-options">
                    <button type="button" class="status-option interview" onclick="selectStatus('interview')">
                        <i class="fas fa-handshake"></i>
                        <span>Собеседование</span>
                    </button>
                    <button type="button" class="status-option hired" onclick="selectStatus('hired')">
                        <i class="fas fa-check-circle"></i>
                        <span>Принят на работу</span>
                    </button>
                    <button type="button" class="status-option rejected" onclick="selectStatus('rejected')">
                        <i class="fas fa-times-circle"></i>
                        <span>Отклонено</span>
                    </button>
                </div>
                <input type="hidden" name="status" id="selectedStatus">
            </form>
        </div>
    </div>

    <script>
    function openStatusModal(applicationId, currentStatus) {
        document.getElementById('modalApplicationId').value = applicationId;
        document.getElementById('selectedStatus').value = currentStatus;
        document.getElementById('statusModal').classList.add('show');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.remove('show');
    }

    function selectStatus(status) {
        document.getElementById('selectedStatus').value = status;
        document.getElementById('statusForm').submit();
    }

    // Закрытие модального окна при клике вне его
    window.onclick = function(event) {
        var modal = document.getElementById('statusModal');
        if (event.target == modal) {
            closeStatusModal();
        }
    }
    </script>
</body>
</html>