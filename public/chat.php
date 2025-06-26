<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header("Location: messenger.php");
    exit();
}

try {
    // Получаем информацию о чате
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.job_id,
            a.hr_id,
            a.candidate_id,
            a.status,
            a.message,
            a.created_at,
            a.entity_type,
            a.entity_id,
            j.title as job_title,
            CASE 
                WHEN a.hr_id = ? THEN u2.name
                ELSE u.name
            END as interlocutor_name,
            CASE 
                WHEN a.hr_id = ? THEN u2.avatar
                ELSE u.avatar
            END as interlocutor_avatar,
            CASE 
                WHEN a.hr_id = ? THEN u2.email
                ELSE u.email
            END as interlocutor_email
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.hr_id = u.id
        JOIN users u2 ON a.candidate_id = u2.id
        WHERE a.id = ? AND (a.hr_id = ? OR a.candidate_id = ?)
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $application_id, $user_id, $user_id]);
    $chat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chat) {
        header("Location: messenger.php");
        exit();
    }

    // Получаем сообщения чата
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            u.name as sender_name,
            u.avatar as sender_avatar
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.application_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$application_id]);
    $messages = $stmt->fetchAll();

} catch(Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}

// Подключаем header.php после всех проверок и перенаправлений
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат - FlowHR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #F8F9FA;
            margin-left: 250px;
            padding: 20px;
        }

        .chat-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            background: #FFFFFF;
            padding: 20px;
            border-bottom: 2px solid #FF6F61;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-button {
            background: #FFFFFF;
            border: 1px solid #E0E0E0;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2C3E50;
        }

        .back-button:hover {
            background: #F5F5F5;
            border-color: #FF6F61;
            transform: translateX(-2px);
        }

        .back-button i {
            color: #FF6F61;
        }

        .chat-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #FF6F61;
        }

        .chat-info {
            flex-grow: 1;
        }

        .chat-title {
            font-size: 1.4rem;
            color: #2C3E50;
            margin: 0 0 5px 0;
            font-weight: 600;
        }

        .chat-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .interlocutor-name {
            font-weight: 500;
            color: #2C3E50;
        }

        .chat-type {
            background: #FFF8E1;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #FF6F61;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .chat-messages {
            padding: 20px;
            height: calc(100vh - 300px);
            overflow-y: auto;
            background: #F8F9FA;
        }

        .message {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease-out;
        }

        .message.sent {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-content {
            max-width: 70%;
        }

        .message-bubble {
            padding: 12px 16px;
            border-radius: 15px;
            position: relative;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .message.received .message-bubble {
            background: #FFFFFF;
            border: 1px solid #E0E0E0;
            border-top-left-radius: 5px;
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: white;
            border-top-right-radius: 5px;
        }

        .message-time {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
            text-align: right;
        }

        .message.sent .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-input {
            padding: 20px;
            background: #FFFFFF;
            border-top: 1px solid #E0E0E0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .message-input {
            flex-grow: 1;
            padding: 12px 16px;
            border: 1px solid #E0E0E0;
            border-radius: 25px;
            font-size: 0.95rem;
            resize: none;
            height: 50px;
            line-height: 26px;
            transition: all 0.3s ease;
            max-height: 200px;
            overflow-y: auto;
        }

        .message-input:focus {
            outline: none;
            border-color: #FF6F61;
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.1);
        }

        .send-button {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: white;
            border: none;
            border-radius: 25px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.3);
        }

        .send-button i {
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending {
            background: #FFF8E1;
            color: #FFC107;
        }

        .status-interview {
            background: #E3F2FD;
            color: #2196F3;
        }

        .status-accepted {
            background: #E8F5E9;
            color: #28A745;
        }

        .status-hired {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-rejected {
            background: #FFEBEE;
            color: #DC3545;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            .chat-header {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }

            .chat-messages {
                height: calc(100vh - 400px);
            }

            .message-content {
                max-width: 85%;
            }
        }

        .status-controls {
            position: relative;
            margin-left: auto;
        }

        .status-btn {
            background: #FFFFFF;
            border: 1px solid #E0E0E0;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .status-btn:hover {
            background: #F5F5F5;
            border-color: #FF6F61;
        }

        .modal {
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

        .modal.show {
            display: flex !important;
        }

        .modal-content {
            background: #FFFFFF;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.3s ease-out;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #E0E0E0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #2C3E50;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .modal-body {
            padding: 20px;
        }

        .confirmation-text {
            margin: 0 0 20px 0;
            color: #2C3E50;
            font-size: 1rem;
            text-align: center;
        }

        .status-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
        }

        .status-option {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: flex-start;
        }

        .status-option.pending-btn {
            background: #FFF8E1;
            color: #FFC107;
        }

        .status-option.pending-btn:hover {
            background: #FFECB3;
        }

        .status-option.interview-btn {
            background: #E3F2FD;
            color: #2196F3;
        }

        .status-option.interview-btn:hover {
            background: #BBDEFB;
        }

        .status-option.accept-btn {
            background: #E8F5E9;
            color: #28A745;
        }

        .status-option.accept-btn:hover {
            background: #C8E6C9;
        }

        .status-option.hired-btn {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-option.hired-btn:hover {
            background: #C8E6C9;
        }

        .status-option.reject-btn {
            background: #FFEBEE;
            color: #DC3545;
        }

        .status-option.reject-btn:hover {
            background: #FFCDD2;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }

        .confirmation-modal.show {
            display: flex !important;
        }

        .confirmation-content {
            background: #FFFFFF;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.3s ease-out;
        }

        .confirmation-title {
            font-size: 1.2rem;
            color: #2C3E50;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .confirmation-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .confirmation-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .confirm-btn {
            background: #FF6F61;
            color: white;
        }

        .confirm-btn:hover {
            background: #FF3B2F;
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: #F5F5F5;
            color: #666;
        }

        .cancel-btn:hover {
            background: #E0E0E0;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <a href="messenger.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Вернуться в мессенджер
            </a>
            <img src="<?php echo file_exists('../uploads/avatars/' . $chat['interlocutor_avatar']) ? '../uploads/avatars/' . $chat['interlocutor_avatar'] : '../uploads/avatars/default_avatar.png'; ?>" 
                 alt="Аватар" 
                 class="chat-avatar"
                 onerror="this.src='../uploads/avatars/default_avatar.png'">
            
            <div class="chat-info">
                <h1 class="chat-title"><?php echo htmlspecialchars($chat['job_title']); ?></h1>
                <div class="chat-subtitle">
                    <span class="interlocutor-name"><?php echo htmlspecialchars($chat['interlocutor_name']); ?></span>
                    <span class="chat-type">
                        <?php if ($chat['entity_type'] === 'vacancy'): ?>
                            <i class="fas fa-briefcase"></i> Отклик на вакансию
                        <?php else: ?>
                            <i class="fas fa-user-tie"></i> Предложение по резюме
                        <?php endif; ?>
                    </span>
                    <span class="status-badge status-<?php echo $chat['status']; ?>" id="statusBadge">
                        <?php 
                        switch($chat['status']) {
                            case 'pending':
                                echo '<i class="fas fa-hourglass-half"></i> На рассмотрении';
                                break;
                            case 'interview':
                                echo '<i class="fas fa-handshake"></i> Собеседование';
                                break;
                            case 'hired':
                                echo '<i class="fas fa-user-tie"></i> Принят на работу';
                                break;
                            case 'rejected':
                                echo '<i class="fas fa-times-circle"></i> Отклонено';
                                break;
                        }
                        ?>
                    </span>
                </div>
                <div style="display:flex; align-items:center; gap:12px; margin-top:10px;">
                    <?php if ($chat['hr_id'] == $_SESSION['user_id'] && $chat['status'] == 'pending'): ?>
                    <div class="status-controls" style="margin:0;">
                        <button class="status-btn" onclick="openStatusModal()">
                            <i class="fas fa-ellipsis-v"></i>
                            Изменить статус <?php echo $chat['entity_type'] === 'vacancy' ? 'отклика' : 'предложения'; ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'candidate'): ?>
                        <button class="status-btn" onclick="openJobModal(<?php echo (int)$chat['job_id']; ?>)"><i class="fas fa-briefcase"></i> Посмотреть вакансию</button>
                    <?php elseif ($_SESSION['role'] === 'HR' && $chat['entity_type'] === 'resume'): ?>
                        <button class="status-btn" onclick="openResumeModal(<?php echo (int)$chat['entity_id']; ?>)"><i class="fas fa-user-tie"></i> Посмотреть резюме</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Модальное окно изменения статуса -->
        <div class="modal" id="statusModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Изменение статуса</h2>
                    <button class="close-btn" onclick="closeStatusModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="confirmation-text">
                        Выберите новый статус <?php echo $chat['entity_type'] === 'vacancy' ? 'отклика' : 'предложения'; ?>:
                    </p>
                    <div class="status-options">
                        <button class="status-option pending-btn" onclick="handleStatusChange('pending')">
                            <i class="fas fa-hourglass-half"></i> На рассмотрении
                        </button>
                        <button class="status-option interview-btn" onclick="handleStatusChange('interview')">
                            <i class="fas fa-handshake"></i> Собеседование
                        </button>
                        <button class="status-option hired-btn" onclick="handleStatusChange('hired')">
                            <i class="fas fa-user-tie"></i> Принят на работу
                        </button>
                        <button class="status-option reject-btn" onclick="handleStatusChange('rejected')">
                            <i class="fas fa-times"></i> Отклонить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Добавляем модальное окно подтверждения -->
        <div class="confirmation-modal" id="confirmationModal">
            <div class="confirmation-content">
                <h2 class="confirmation-title">Подтверждение изменения статуса</h2>
                <p>Вы уверены, что хотите изменить статус?</p>
                <div class="confirmation-buttons">
                    <button class="confirmation-btn confirm-btn" id="confirmStatusBtn">Подтвердить</button>
                    <button class="confirmation-btn cancel-btn" onclick="closeConfirmationModal()">Отмена</button>
                </div>
            </div>
        </div>

        <!-- Модальное окно для резюме -->
        <div class="modal" id="resumeModal">
            <div class="modal-content" style="max-width:600px;">
                <div class="modal-header">
                    <h2>Резюме</h2>
                    <button class="close-btn" onclick="closeResumeModal()">&times;</button>
                </div>
                <div class="modal-body" id="resumeModalBody">
                    <div class="text-center">Загрузка...</div>
                </div>
            </div>
        </div>
        <!-- Модальное окно для вакансии -->
        <div class="modal" id="jobModal">
            <div class="modal-content" style="max-width:600px;">
                <div class="modal-header">
                    <h2>Вакансия</h2>
                    <button class="close-btn" onclick="closeJobModal()">&times;</button>
                </div>
                <div class="modal-body" id="jobModalBody">
                    <div class="text-center">Загрузка...</div>
                </div>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['user_id'] == $user_id ? 'sent' : 'received'; ?>">
                    <img src="<?php echo file_exists('../uploads/avatars/' . $message['sender_avatar']) ? '../uploads/avatars/' . $message['sender_avatar'] : '../uploads/avatars/default_avatar.png'; ?>" 
                         alt="Аватар" 
                         class="message-avatar"
                         onerror="this.src='../uploads/avatars/default_avatar.png'">
                    
                    <div class="message-content">
                        <div class="message-bubble">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                        <div class="message-time">
                            <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="chat-input" id="messageForm">
            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <textarea 
                class="message-input" 
                name="message" 
                placeholder="Введите сообщение..."
                required
            ></textarea>
            <button type="submit" class="send-button">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chatMessages');
            const messageForm = document.getElementById('messageForm');
            const messageInput = messageForm.querySelector('textarea');
            let lastMessageId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;

            // Автоувеличение textarea
            messageInput.addEventListener('input', function() {
                this.style.height = '50px';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });

            // Прокрутка к последнему сообщению
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Функции для работы с модальным окном
            window.openStatusModal = function() {
                const modal = document.getElementById('statusModal');
                if (modal) {
                    modal.classList.add('show');
                }
            };

            window.closeStatusModal = function() {
                const modal = document.getElementById('statusModal');
                if (modal) {
                    modal.classList.remove('show');
                }
            };

            // Функция обработки изменения статуса
            window.handleStatusChange = function(status) {
                console.log('Handling status change:', status);
                if (confirm('Вы уверены, что хотите изменить статус?')) {
                    updateStatus(status);
                }
            };

            // Функция обновления статуса
            async function updateStatus(status) {
                try {
                    console.log('Updating status to:', status);
                    
                    const formData = new FormData();
                    formData.append('application_id', <?php echo $application_id; ?>);
                    formData.append('status', status);

                    console.log('Sending request with status:', status);
                    const response = await fetch('update_status.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    console.log('Response:', data);
                    
                    if (data.success) {
                        // Обновляем отображение статуса
                        const statusBadge = document.getElementById('statusBadge');
                        statusBadge.className = `status-badge status-${status}`;
                        
                        let statusText = '';
                        switch(status) {
                            case 'pending':
                                statusText = '<i class="fas fa-hourglass-half"></i> На рассмотрении';
                                break;
                            case 'interview':
                                statusText = '<i class="fas fa-handshake"></i> Собеседование';
                                break;
                            case 'hired':
                                statusText = '<i class="fas fa-user-tie"></i> Принят на работу';
                                break;
                            case 'rejected':
                                statusText = '<i class="fas fa-times-circle"></i> Отклонено';
                                break;
                        }
                        
                        statusBadge.innerHTML = statusText;
                        
                        // Скрываем кнопку управления статусом
                        const statusControls = document.querySelector('.status-controls');
                        if (statusControls) {
                            statusControls.style.display = 'none';
                        }

                        // Закрываем модальное окно
                        closeStatusModal();
                    } else {
                        alert('Ошибка при обновлении статуса: ' + data.error);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ошибка при обновлении статуса: ' + error.message);
                }
            }

            // Функции для работы с модальным окном подтверждения
            let selectedStatus = null;

            window.openConfirmationModal = function(status) {
                console.log('Opening confirmation modal with status:', status);
                selectedStatus = status;
                const modal = document.getElementById('confirmationModal');
                if (modal) {
                    modal.classList.add('show');
                }
            };

            window.closeConfirmationModal = function() {
                const modal = document.getElementById('confirmationModal');
                if (modal) {
                    modal.classList.remove('show');
                }
                selectedStatus = null;
            };

            // Обработчик подтверждения
            document.getElementById('confirmStatusBtn').addEventListener('click', function() {
                console.log('Confirm button clicked, selected status:', selectedStatus);
                if (selectedStatus) {
                    closeConfirmationModal();
                    closeStatusModal();
                    updateStatus(selectedStatus);
                } else {
                    console.error('No status selected');
                    alert('Ошибка: статус не выбран');
                }
            });

            // Обработка нажатия Enter
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const message = messageInput.value.trim();
                    if (message) {
                        sendMessage(message);
                    }
                }
            });

            // Обработка отправки сообщения
            messageForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (message) {
                    sendMessage(message);
                }
            });

            // Функция отправки сообщения
            async function sendMessage(message) {
                try {
                    const formData = new FormData(messageForm);
                    formData.append('message', message);

                    const response = await fetch('send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams(formData)
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            // Добавляем новое сообщение в чат
                            const messageHtml = `
                                <div class="message sent">
                                    <img src="${data.avatar ? '../uploads/avatars/' + data.avatar : '../uploads/avatars/default_avatar.png'}" 
                                         alt="Аватар" 
                                         class="message-avatar"
                                         onerror="this.src='../uploads/avatars/default_avatar.png'">
                                    
                                    <div class="message-content">
                                        <div class="message-bubble">
                                            ${message}
                                        </div>
                                        <div class="message-time">
                                            ${new Date().toLocaleString('ru-RU')}
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                            messageInput.value = '';
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            lastMessageId = data.message_id;
                        } else {
                            alert('Ошибка при отправке сообщения: ' + data.error);
                        }
                    } else {
                        throw new Error('Ошибка сети');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ошибка при отправке сообщения. Пожалуйста, попробуйте еще раз.');
                }
            }

            // Функция получения новых сообщений
            async function getNewMessages() {
                try {
                    console.log('Checking for new messages...');
                    const response = await fetch(`get_messages.php?application_id=<?php echo $application_id; ?>&last_id=${lastMessageId}`);
                    const data = await response.json();
                    
                    if (data.success && data.messages && data.messages.length > 0) {
                        console.log('New messages found:', data.messages.length);
                        data.messages.forEach(message => {
                            const messageHtml = `
                                <div class="message ${message.user_id == <?php echo $user_id; ?> ? 'sent' : 'received'}">
                                    <img src="${message.sender_avatar ? '../uploads/avatars/' + message.sender_avatar : '../uploads/avatars/default_avatar.png'}" 
                                         alt="Аватар" 
                                         class="message-avatar"
                                         onerror="this.src='../uploads/avatars/default_avatar.png'">
                                    
                                    <div class="message-content">
                                        <div class="message-bubble">
                                            ${message.content}
                                        </div>
                                        <div class="message-time">
                                            ${message.created_at}
                                        </div>
                                    </div>
                                </div>
                            `;
                            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                            lastMessageId = message.id;
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                } catch (error) {
                    console.error('Error fetching messages:', error);
                }
            }

            // Функция проверки статуса
            async function checkStatus() {
                try {
                    const response = await fetch(`check_status.php?application_id=<?php echo $application_id; ?>`);
                    const data = await response.json();
                    
                    if (data.success && data.status) {
                        const statusBadge = document.getElementById('statusBadge');
                        if (statusBadge) {
                            statusBadge.className = `status-badge status-${data.status}`;
                            
                            let statusText = '';
                            switch(data.status) {
                                case 'pending':
                                    statusText = '<i class="fas fa-hourglass-half"></i> На рассмотрении';
                                    break;
                                case 'interview':
                                    statusText = '<i class="fas fa-handshake"></i> Собеседование';
                                    break;
                                case 'hired':
                                    statusText = '<i class="fas fa-user-tie"></i> Принят на работу';
                                    break;
                                case 'rejected':
                                    statusText = '<i class="fas fa-times-circle"></i> Отклонено';
                                    break;
                            }
                            
                            statusBadge.innerHTML = statusText;

                            // Скрываем кнопку управления статусом, если статус не pending
                            const statusControls = document.querySelector('.status-controls');
                            if (statusControls && data.status !== 'pending') {
                                statusControls.style.display = 'none';
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error checking status:', error);
                }
            }

            // Запускаем обновление сообщений каждые 3 секунды
            const messageInterval = setInterval(getNewMessages, 3000);

            // Запускаем проверку статуса каждые 10 секунд
            const statusInterval = setInterval(checkStatus, 10000);

            // Очистка интервалов при уходе со страницы
            window.addEventListener('beforeunload', function() {
                clearInterval(messageInterval);
                clearInterval(statusInterval);
            });

            // Автоматическая прокрутка при получении новых сообщений
            const observer = new MutationObserver(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });

            observer.observe(chatMessages, { childList: true });

            window.openResumeModal = function(resumeId) {
                const modal = document.getElementById('resumeModal');
                const body = document.getElementById('resumeModalBody');
                body.innerHTML = '<div class="text-center">Загрузка...</div>';
                modal.classList.add('show');
                fetch('api/get_resume_info.php?id=' + resumeId)
                    .then(r => r.text())
                    .then(html => { body.innerHTML = html; })
                    .catch(() => { body.innerHTML = '<div class="text-danger">Ошибка загрузки резюме</div>'; });
            }
            window.closeResumeModal = function() {
                document.getElementById('resumeModal').classList.remove('show');
            }
            window.openJobModal = function(jobId) {
                const modal = document.getElementById('jobModal');
                const body = document.getElementById('jobModalBody');
                body.innerHTML = '<div class="text-center">Загрузка...</div>';
                modal.classList.add('show');
                fetch('api/get_job_info.php?id=' + jobId)
                    .then(r => r.text())
                    .then(html => { body.innerHTML = html; })
                    .catch(() => { body.innerHTML = '<div class="text-danger">Ошибка загрузки вакансии</div>'; });
            }
            window.closeJobModal = function() {
                document.getElementById('jobModal').classList.remove('show');
            }
        });
    </script>
</body>
</html>