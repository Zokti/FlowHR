<?php
require 'includes/config.php';
require 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - FlowHR</title>
    <!-- Подключаем Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Общие стили */
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF; /* Белый фон */
            color: #2C3E50; /* Темно-серый текст */
            margin: 0;
            padding: 0;
            line-height: 1.6;
            margin-left: 250px; /* Отступ для бокового меню */
        }

        /* Адаптация для мобильных устройств */
        @media (max-width: 768px) {
            body {
                margin-left: 0; /* Убираем отступ для мобильных устройств */
            }

            .container {
                padding: 10px; /* Уменьшаем отступы */
            }

            .hero {
                padding: 50px 0; /* Уменьшаем отступы */
            }

            .hero h1 {
                font-size: 36px; /* Уменьшаем размер заголовка */
            }

            .hero p {
                font-size: 16px; /* Уменьшаем размер текста */
            }

            .features-grid {
                display: flex;
                overflow-x: auto; /* Горизонтальная прокрутка */
                gap: 20px;
                padding-bottom: 20px; /* Отступ для скролла */
                scroll-snap-type: x mandatory; /* Плавная прокрутка */
            }

            .feature-card {
                flex: 0 0 auto; /* Карточки не сжимаются */
                width: 80%; /* Ширина карточки */
                scroll-snap-align: start; /* Плавная прокрутка */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                border-radius: 10px;
                padding: 20px;
                background: white;
                border: 1px solid #E0E0E0;
            }

            .feature-card:hover {
                transform: none; /* Убираем анимацию на мобильных устройствах */
            }
        }

        /* Остальные стили остаются без изменений */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #FFF8E1; /* Очень светлый бежевый фон */
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Легкая тень */
        }

        .hero {
            text-align: center;
            padding: 100px 0;
            background: #FFFFFF; /* Белый фон */
            color: #2C3E50; /* Темно-серый текст */
            border-radius: 10px;
            margin-bottom: 50px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Легкая тень */
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease-in-out;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            animation: fadeInUp 1.2s ease-in-out;
        }

        .hero .cta-button {
            background: #FF6F61; /* Коралловый цвет */
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            transition: background 0.3s ease-in-out, color 0.3s ease-in-out;
            animation: fadeInUp 1.4s ease-in-out;
        }

        .hero .cta-button:hover {
            background: #FF3B2F; /* Темно-коралловый при наведении */
        }

        .about-section {
            padding: 50px 0;
            text-align: center;
        }

        .about-section h2 {
            font-size: 36px;
            color: #2C3E50; /* Темно-серый цвет */
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .about-section p {
            font-size: 18px;
            max-width: 800px;
            margin: 0 auto 30px;
            color: #2C3E50;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .about-section img {
            max-width: 100%;
            border-radius: 10px;
            animation: fadeInUp 1s ease-in-out;
        }

        .features-section {
            padding: 50px 0;
            text-align: center;
        }

        .features-section h2 {
            font-size: 36px;
            color: #2C3E50; /* Темно-серый цвет */
            margin-bottom: 40px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .features-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0; /* Светло-серая граница */
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-card i {
            font-size: 40px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 20px;
            color: #2C3E50;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 14px;
            color: #666666; /* Серый текст */
        }

        .cta-section {
            padding: 50px 0;
            text-align: center;
            background: #FFFFFF; /* Белый фон */
            color: #2C3E50; /* Темно-серый текст */
            border-radius: 10px;
            margin: 50px 0;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Легкая тень */
        }

        .cta-section h2 {
            font-size: 36px;
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .cta-section p {
            font-size: 18px;
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .cta-section .cta-button {
            background: #FF6F61; /* Коралловый цвет */
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            transition: background 0.3s ease-in-out, color 0.3s ease-in-out;
            animation: fadeInUp 1s ease-in-out;
        }

        .cta-section .cta-button:hover {
            background: #FF3B2F; /* Темно-коралловый при наведении */
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Приветствие -->
    <div class="hero">
        <h1>FlowHR — ваш помощник в подборе персонала</h1>
        <p>Упрощаем HR-процессы и находим лучших кандидатов для вашей компании.</p>
        <a href="#" class="cta-button">Начать сейчас</a>
    </div>

    <!-- О проекте -->
    <div class="about-section">
        <h2>О нашем проекте</h2>
        <p>
            FlowHR — это инновационная платформа для HR-специалистов, которая помогает автоматизировать процессы подбора персонала, 
            управлять вакансиями и находить идеальных кандидатов. Мы стремимся сделать работу HR-отделов проще, быстрее и эффективнее.
        </p>
    </div>

    <!-- Возможности -->
    <div class="features-section">
        <h2>Наши возможности</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-search"></i>
                <h3>Поиск кандидатов</h3>
                <p>Ищите кандидатов по навыкам, опыту и локации.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-tasks"></i>
                <h3>Управление вакансиями</h3>
                <p>Создавайте и управляйте вакансиями в одном месте.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-bar"></i>
                <h3>Аналитика</h3>
                <p>Получайте отчеты и аналитику по всем этапам подбора.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-comments"></i>
                <h3>Чат с кандидатами</h3>
                <p>Общайтесь с кандидатами прямо на платформе.</p>
            </div>
        </div>
    </div>

    <!-- Призыв к действию -->
    <div class="cta-section">
        <h2>Готовы оптимизировать HR-процессы?</h2>
        <p>Присоединяйтесь к FlowHR и начните работать эффективнее уже сегодня!</p>
        <a href="#" class="cta-button">Присоединиться</a>
    </div>
</body>
</html>