<?php
require 'header_logic.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Основные стили для header */
        header {
            background: #2C3E50; /* Темно-серый фон */
            width: 250px; /* Ширина бокового меню */
            height: 100vh; /* На всю высоту экрана */
            position: fixed; /* Фиксированное положение */
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease; /* Анимация для выдвижения */
            z-index: 1000; /* Убедимся, что меню поверх других элементов */
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
                transform: translateX(-100%);
            }

            /* Показываем header при добавлении класса .active */
            header.active {
                transform: translateX(0);
            }

            /* Убираем отступ у body на мобильных устройствах */
            body {
                margin-left: 0;
            }
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #FF6F61; /* Коралловый цвет */
            text-align: center;
            margin-bottom: 20px;
        }

        .user-info {
            color: #FFFFFF; /* Белый текст */
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info h3 {
            margin: 0;
            font-size: 18px;
        }

        .user-info p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #FF6F61; /* Коралловый цвет для роли */
        }

        nav {
            display: flex;
            flex-direction: column;
        }

        nav a {
            color: #FFFFFF; /* Белый текст */
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            border-radius: 5px;
            transition: background 0.2s, color 0.2s;
        }

        nav a:hover {
            background: #FF6F61; /* Коралловый фон при наведении */
            color: #FFFFFF; /* Белый текст при наведении */
        }

        nav a i {
            margin-right: 10px; /* Отступ между иконкой и текстом */
        }

        /* Стили для иконок */
        .fa-home { color: #FF6F61; }
        .fa-sign-in-alt { color: #28a745; }
        .fa-user-plus { color: #007bff; }
        .fa-user { color: #FF6F61; }
        .fa-sign-out-alt { color: #dc3545; }
        .fa-comments { color: #FF6F61; }
        .fa-briefcase { color: #FF6F61; }
        .fa-file-alt { color: #FF6F61; }
        .fa-clipboard-list { color: #FF6F61; }
        .fa-chart-line { color: #FF6F61; }
        .fa-users-cog { color: #FF6F61; } /* Для модератора */
        .fa-tools { color: #FF6F61; } /* Для админа */
        .fa-user-check { color: #FF6F61; } /* Для модерации кандидатов */
        .fa-database { color: #FF6F61; } /* Для справочных данных */
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
    <a href="../index.php"><i class="fas fa-home" style="color: #FF6F61;"></i>Главная</a>
    <a href="../public/about.php"><i class="fas fa-info-circle" style="color: #FF6F61;"></i>О нас</a>
    <a href="../public/contacts.php"><i class="fas fa-map-marker-alt" style="color: #FF6F61;"></i>Контакты</a>
    <?php if (!$user_id): ?>
        <a href="../public/auth.php"><i class="fas fa-sign-in-alt" style="color: #FF6F61;"></i>Вход в систему</a>
    <?php else: ?>
        <a href="../public/profile.php"><i class="fas fa-user" style="color: #FF6F61;"></i>Профиль</a>
        <a href="../public/logout.php"><i class="fas fa-sign-out-alt" style="color: #FF6F61;"></i>Выход</a>

        <!-- Для кандидата -->
        <?php if ($user_role === 'candidate'): ?>
            <a href="../public/messenger.php"><i class="fas fa-comments" style="color: #FF6F61;"></i>Мессенджер</a>
            <a href="../public/jobs.php"><i class="fas fa-briefcase" style="color: #FF6F61;"></i>Вакансии</a>
            <a href="../public/my_applications.php"><i class="fas fa-file-alt" style="color: #FF6F61;"></i>Мои отклики</a>
            <a href="../public/tests.php"><i class="fas fa-clipboard-list" style="color: #FF6F61;"></i>Тесты</a>
        <?php endif; ?>

        <!-- Для HR -->
        <?php if ($user_role === 'HR'): ?>
            <a href="../public/messenger.php"><i class="fas fa-comments" style="color: #FF6F61;"></i>Мессенджер</a>
            <a href="../public/my_vacancies.php"><i class="fas fa-briefcase" style="color: #FF6F61;"></i>Мои вакансии</a>
            <a href="../public/create_vacancy.php"><i class="fas fa-plus" style="color: #FF6F61;"></i>Создание вакансии</a>
            <a href="../public/applications.php"><i class="fas fa-file-alt" style="color: #FF6F61;"></i>Отклики на вакансии</a>
            <a href="../public/candidates.php"><i class="fas fa-users" style="color: #FF6F61;"></i>Кандидаты</a>
            <a href="../public/analytics.php"><i class="fas fa-chart-line" style="color: #FF6F61;"></i>Аналитика</a>
        <?php endif; ?>

        <!-- Для модератора -->
        <?php if ($user_role === 'moderator'): ?>
            <a href="../AModerator/moderate_vacancies.php"><i class="fas fa-briefcase" style="color: #FF6F61;"></i>Модерация вакансий</a>
            <a href="../AModerator/moderate_candidates.php"><i class="fas fa-user-check" style="color: #FF6F61;"></i>Модерация кандидатов</a>
            <a href="../AModerator/moderate_messages.php"><i class="fas fa-comments" style="color: #FF6F61;"></i>Модерация сообщений</a>
        <?php endif; ?>

        <!-- Для администратора -->
        <?php if ($user_role === 'admin'): ?>
            <a href="../Admin/manage_users.php"><i class="fas fa-users-cog" style="color: #FF6F61;"></i>Управление пользователями</a>
            <a href="../Admin/manage_jobs.php"><i class="fas fa-tools" style="color: #FF6F61;"></i>Управление вакансиями</a>
            <a href="../Admin/list_data.php"><i class="fas fa-database" style="color: #FF6F61;"></i>Управление справочными данными</a>
            <a href="../Admin/manage_test.php"><i class="fas fa-clipboard-list" style="color: #FF6F61;"></i>Управление тестами</a>
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