<?php
require 'header_logic.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>FlowHR</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Основные стили для header */
        header {
            background: #2C3E50 !important;
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            height: 100vh !important;
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 20px !important;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1) !important;
            transition: transform 0.3s ease !important;
            z-index: 1000 !important;
            transform: translateZ(0) !important;
            backface-visibility: hidden !important;
            perspective: 1000px !important;
            will-change: transform !important;
        }

        /* Добавляем стили для body */
        body {
            margin-left: 250px !important;
            transition: margin-left 0.3s ease !important;
            min-height: 100vh !important;
            overflow-x: hidden !important;
        }

        /* Кнопка для открытия меню (скрыта по умолчанию) */
        .menu-toggle {
            display: none; /* По умолчанию скрываем */
            position: fixed;
            top: 20px;
            left: 20px;
            background: #2C3E50;
            color: #FFFFFF;
            border: none;
            padding: 10px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1001; /* Кнопка поверх меню */
            border-radius: 5px;
        }

        /* Стили для мобильных устройств */
        @media (max-width: 768px) {
            /* Показываем кнопку на мобильных устройствах */
            .menu-toggle {
                display: block;
            }

            /* Скрываем header за пределами экрана */
            header {
                transform: translateX(-100%) translateZ(0) !important;
            }

            /* Показываем header при добавлении класса .active */
            header.active {
                transform: translateX(0) translateZ(0) !important;
            }

            /* Убираем отступ у body на мобильных устройствах */
            body {
                margin-left: 0 !important;
            }
        }

        .logo {
            font-size: 24px !important;
            font-weight: bold !important;
            color: #FF6F61 !important;
            text-align: center !important;
            margin-bottom: 20px !important;
        }

        .user-info {
            color: #FFFFFF !important;
            margin-bottom: 20px !important;
            text-align: center !important;
            width: 100% !important;
            padding: 0 10px !important;
            box-sizing: border-box !important;
        }

        .user-info h3 {
            margin: 0 !important;
            font-size: 18px !important;
            font-weight: 500 !important;
            white-space: normal !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
            line-height: 1.3 !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 3 !important;
            -webkit-box-orient: vertical !important;
            word-wrap: break-word !important;
            hyphens: auto !important;
        }

        .user-info p {
            margin: 5px 0 0 !important;
            font-size: 14px !important;
            color: #FF6F61 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
        }

        nav {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            transform: translateZ(0) !important;
            backface-visibility: hidden !important;
        }

        nav a {
            color: #FFFFFF !important;
            text-decoration: none !important;
            padding: 12px 15px !important;
            margin: 5px 0 !important;
            font-size: 15px !important;
            display: flex !important;
            align-items: center !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
            font-weight: 500 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            transform: translateZ(0) !important;
            backface-visibility: hidden !important;
        }

        nav a:hover {
            background: rgba(255, 111, 97, 0.1) !important;
            color: #FF6F61 !important;
            transform: translateX(5px) translateZ(0) !important;
        }

        nav a i {
            margin-right: 12px !important;
            width: 20px !important;
            text-align: center !important;
            font-size: 16px !important;
            flex-shrink: 0 !important;
            color: #FF6F61 !important;
            transition: all 0.3s ease !important;
        }

        nav a:hover i {
            transform: scale(1.2) !important;
            color: #FF6F61 !important;
        }

        /* Удаляем старые стили для отдельных иконок */
        .fa-home, .fa-info-circle, .fa-map-marker-alt, .fa-sign-in-alt, 
        .fa-user, .fa-sign-out-alt, .fa-comments, .fa-file-alt, 
        .fa-briefcase, .fa-plus-circle, .fa-users, .fa-chart-line, 
        .fa-clipboard-list, .fa-user-check, .fa-users-cog, .fa-tools,
        .fa-clipboard-check {
            color: #FF6F61 !important;
            transition: all 0.3s ease !important;
        }

        /* Удаляем специальные анимации для разных иконок */
        nav a:hover .fa-plus-circle,
        nav a:hover .fa-chart-line,
        nav a:hover .fa-comments,
        nav a:hover .fa-file-alt,
        nav a:hover .fa-briefcase,
        nav a:hover .fa-users,
        nav a:hover .fa-clipboard-list,
        nav a:hover .fa-user-check,
        nav a:hover .fa-users-cog,
        nav a:hover .fa-tools,
        nav a:hover .fa-sign-out-alt,
        nav a:hover .fa-sign-in-alt,
        nav a:hover .fa-map-marker-alt,
        nav a:hover .fa-info-circle,
        nav a:hover .fa-home,
        nav a:hover .fa-clipboard-check {
            transform: scale(1.2) !important;
            color: #FF6F61 !important;
        }

        .sidebar {
            display: none !important;
        }

        /* Добавляем стили для предотвращения мерцания при загрузке */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        header {
            animation: fadeIn 0.3s ease-in-out !important;
        }
    </style>
</head>
<body>
    <!-- Кнопка для открытия меню -->
    <button class="menu-toggle" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <header id="sidebar">
        <!-- Логотип -->
        <div class="logo">FlowHR</div>

        <!-- Информация о пользователе -->
        <?php if ($user_id): ?>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p><?php echo htmlspecialchars($roleText); ?></p>
            </div>
        <?php endif; ?>

        <!-- Навигация -->
        <nav>
            <a href="../index.php"><i class="fas fa-home"></i>Главная</a>
            <a href="../public/about.php"><i class="fas fa-info-circle"></i>О нас</a>
            <a href="../public/contacts.php"><i class="fas fa-address-book"></i>Контакты</a>
            <?php if (!$user_id): ?>
                <a href="../public/auth.php"><i class="fas fa-sign-in-alt"></i>Вход в систему</a>
            <?php else: ?>
                <a href="../public/profile.php"><i class="fas fa-user-circle"></i>Профиль</a>
                <a href="../public/logout.php"><i class="fas fa-sign-out-alt"></i>Выход</a>

                <!-- Для кандидата -->
                <?php if ($user_role === 'candidate'): ?>
                    <a href="../public/messenger.php"><i class="fas fa-comments"></i>Мессенджер</a>
                    <a href="../public/create_resume.php"><i class="fas fa-file-medical"></i>Создать резюме</a>
                    <a href="../public/my_resume.php"><i class="fas fa-file-alt"></i>Мои резюме</a>
                    <a href="../public/jobs.php"><i class="fas fa-search"></i>Вакансии</a>
                    <a href="../public/my_applications.php"><i class="fas fa-clipboard-check"></i>Отклики</a>
                    <a href="../public/tests.php"><i class="fas fa-tasks"></i>Тесты</a>
                <?php endif; ?>

                <!-- Для HR -->
                <?php if ($user_role === 'HR'): ?>
                    <a href="../public/messenger.php"><i class="fas fa-comments"></i>Мессенджер</a>
                    <a href="../public/my_vacancies.php"><i class="fas fa-briefcase"></i>Мои вакансии</a>
                    <a href="../public/create_vacancy.php"><i class="fas fa-plus-circle"></i>Создание вакансии</a>
                    <a href="../public/applications.php"><i class="fas fa-clipboard-list"></i>Отклики на вакансии</a>
                    <a href="../public/view_resume.php"><i class="fas fa-search"></i>Просмотр резюме</a>
                    <a href="../public/auto_match.php"><i class="fas fa-magic"></i>Автоматический подбор персонала</a>
                    <a href="../public/analytics.php"><i class="fas fa-chart-bar"></i>Аналитика</a>
                <?php endif; ?>

                <!-- Для модератора -->
                <?php if ($user_role === 'moderator'): ?>
                    <a href="../AModerator/moderate_vacancies.php"><i class="fas fa-clipboard-check"></i>Модерация вакансий</a>
                    <a href="../AModerator/moderate_candidates.php"><i class="fas fa-user-shield"></i>Модерация кандидатов</a>
                    <a href="../AModerator/moderate_messages.php"><i class="fas fa-comment-dots"></i>Модерация сообщений</a>
                <?php endif; ?>

                <!-- Для администратора -->
                <?php if ($user_role === 'admin'): ?>
                    <a href="../Admin/manage_users.php"><i class="fas fa-users-cog"></i>Управление пользователями</a>
                    <a href="../Admin/manage_jobs.php"><i class="fas fa-tasks"></i>Управление вакансиями</a>
                    <a href="../Admin/manage_test.php"><i class="fas fa-clipboard-list"></i>Управление тестами</a>
                    <a href="../Admin/list_data.php"><i class="fas fa-database"></i>Справочные данные</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </header>

    <script>
        // Функция для открытия/закрытия меню
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>