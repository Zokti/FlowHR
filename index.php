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
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-button {
                width: 90%;
                margin-bottom: 15px;
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
        
        /* Новые стили для кнопок действий */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .action-button {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 300px;
            text-decoration: none;
            color: #2C3E50;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .action-button i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .action-button h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .action-button p {
            font-size: 16px;
            color: #666;
        }
        
        .action-button.candidate {
            border-top: 5px solid #FF6F61; /* Зеленый акцент */
        }
        
        .action-button.candidate i {
            color: #FF6F61;
        }
        
        .action-button.hr {
            border-top: 5px solid #2196F3; /* Синий акцент */
        }
        
        .action-button.hr i {
            color: #2196F3;
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
         /* Стили для призыва к действию */
         .cta-section {
            padding: 50px 0;
            text-align: center;
            background: #FFFFFF;
            border-radius: 10px;
            margin: 50px 0;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
        .action-button {
            text-decoration: none;
            color: #2C3E50;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .action-button.candidate, .action-button.hr {
            border-top: 5px solid #FF6F61;
        }
  /* Карточки возможностей */
  .features-grid, .action-buttons {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .feature-card, .action-button {
            background: white;
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }
        .feature-card:hover, .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .feature-card i, .action-button i {
            font-size: 40px;
            color: #FF6F61;
            margin-bottom: 15px;
        }
        .feature-card h3, .action-button h3 {
            font-size: 20px;
            color: #2C3E50;
            margin-bottom: 10px;
        }
        .feature-card p, .action-button p {
            font-size: 14px;
            color: #666666;
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

        /* Стили для новых секций */
        .benefits-section, .stats-section, .process-section, .advantages-section {
            padding: 50px 0;
            text-align: center;
            background: #FFFFFF;
            border-radius: 10px;
            margin: 30px 0;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .benefits-section h2, .stats-section h2, 
        .process-section h2, .advantages-section h2 {
            font-size: 36px;
            color: #2C3E50;
            margin-bottom: 40px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .benefits-grid, .stats-grid, 
        .process-grid, .advantages-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .benefit-card {
            background: white;
            border-radius: 10px;
            padding: 30px 20px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: all 0.3s ease;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .benefit-card i {
            font-size: 48px;
            color: #FF6F61;
            margin-bottom: 20px;
        }

        .stats-grid {
            gap: 40px;
        }

        .stat-card {
            background: #FF6F61;
            color: white;
            border-radius: 10px;
            padding: 30px;
            width: 200px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .stat-value {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 18px;
        }

        .process-card {
            background: white;
            border-radius: 10px;
            padding: 30px 20px;
            width: 250px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: all 0.3s ease;
            position: relative;
        }

        .process-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .process-number {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #FF6F61;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .process-card i {
            font-size: 48px;
            color: #FF6F61;
            margin: 20px 0;
        }

        .process-card h3 {
            font-size: 20px;
            color: #2C3E50;
            margin-bottom: 15px;
        }

        .process-card p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .advantage-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: all 0.3s ease;
        }

        .advantage-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .advantage-card i {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .advantage-card.hr-advantage i {
            color: #2196F3;
        }

        .advantage-card.candidate-advantage i {
            color: #FF6F61;
        }

        .advantage-card h3 {
            font-size: 24px;
            color: #2C3E50;
            margin-bottom: 20px;
        }

        .advantage-card ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }

        .advantage-card ul li {
            padding: 10px 0;
            color: #666;
            position: relative;
            padding-left: 25px;
        }

        .advantage-card ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #FF6F61;
            font-weight: bold;
        }

        /* Адаптация для мобильных */
        @media (max-width: 768px) {
            .benefit-card, .stat-card, 
            .process-card, .advantage-card {
                width: 90%;
                margin: 0 auto 20px;
            }
            
            .stats-grid, .process-grid, 
            .advantages-grid {
                flex-direction: column;
            }
        }

        /* --- ДОБАВЛЕНЫ КРАСИВЫЕ АНИМАЦИИ --- */
        .hero, .about-section, .features-section, .benefits-section, .stats-section, .process-section, .advantages-section {
            opacity: 0;
            transform: translateY(60px) scale(0.98);
            transition: opacity 0.9s cubic-bezier(.77,0,.18,1), transform 0.9s cubic-bezier(.77,0,.18,1);
        }
        .hero.in-view, .about-section.in-view, .features-section.in-view, .benefits-section.in-view, .stats-section.in-view, .process-section.in-view, .advantages-section.in-view {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .feature-card, .benefit-card, .stat-card, .process-card, .advantage-card {
            opacity: 0;
            transform: scale(0.95) translateY(40px);
            transition: opacity 0.7s cubic-bezier(.77,0,.18,1), transform 0.7s cubic-bezier(.77,0,.18,1);
        }
        .feature-card.in-view, .benefit-card.in-view, .stat-card.in-view, .process-card.in-view, .advantage-card.in-view {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        .feature-card:hover, .benefit-card:hover, .stat-card:hover, .process-card:hover, .advantage-card:hover {
            box-shadow: 0 16px 32px rgba(255,111,97,0.15), 0 2px 8px rgba(44,62,80,0.08);
            transform: scale(1.04) translateY(-8px) !important;
            z-index: 2;
        }
        .hero h1, .about-section h2, .features-section h2, .benefits-section h2, .stats-section h2, .process-section h2, .advantages-section h2 {
            transition: letter-spacing 0.5s, color 0.5s;
        }
        .hero h1:hover, .about-section h2:hover, .features-section h2:hover, .benefits-section h2:hover, .stats-section h2:hover, .process-section h2:hover, .advantages-section h2:hover {
            letter-spacing: 2px;
            color: #FF6F61;
        }
        .feature-card i, .benefit-card i, .stat-card i, .process-card i, .advantage-card i {
            transition: color 0.5s, transform 0.5s;
        }
        .feature-card:hover i, .benefit-card:hover i, .stat-card:hover i, .process-card:hover i, .advantage-card:hover i {
            color: #2196F3;
            transform: scale(1.2) rotate(-8deg);
        }
        /* Плавное появление текста */
        .feature-card h3, .benefit-card h3, .stat-card h3, .process-card h3, .advantage-card h3 {
            opacity: 0.7;
            transition: opacity 0.5s, color 0.5s;
        }
        .feature-card.in-view h3, .benefit-card.in-view h3, .stat-card.in-view h3, .process-card.in-view h3, .advantage-card.in-view h3 {
            opacity: 1;
            color: #FF6F61;
        }
        /* --- КОНЕЦ ДОБАВЛЕННЫХ АНИМАЦИЙ --- */
    </style>
    <script>
    // Анимация появления секций при скролле
    document.addEventListener('DOMContentLoaded', function() {
        const animatedSections = document.querySelectorAll('.hero, .about-section, .features-section, .benefits-section, .stats-section, .process-section, .advantages-section');
        const animatedCards = document.querySelectorAll('.feature-card, .benefit-card, .stat-card, .process-card, .advantage-card');

        function animateOnScroll(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        }

        const sectionObserver = new IntersectionObserver(animateOnScroll, { threshold: 0.2 });
        animatedSections.forEach(section => {
            sectionObserver.observe(section);
        });
        animatedCards.forEach(card => {
            sectionObserver.observe(card);
        });
    });
    </script>
</head>
<body>
    <!-- Приветствие -->
    <div class="hero">
        <h1>FlowHR — ваш помощник в подборе персонала</h1>
        <p>Упрощаем HR-процессы и находим лучших кандидатов для вашей компании.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="public/auth.php" class="cta-button">Начать сейчас</a>
        <?php endif; ?>
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

    <!-- Секция "Почему выбирают нас" -->
    <div class="benefits-section">
        <h2>Почему выбирают FlowHR</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Безопасность данных</h3>
                <p>Ваши данные защищены современными методами шифрования и соответствуют GDPR</p>
            </div>
            <div class="benefit-card">
                <i class="fas fa-rocket"></i>
                <h3>Экономия времени</h3>
                <p>Сократите время подбора на 40% с помощью автоматизированных процессов</p>
            </div>
            <div class="benefit-card">
                <i class="fas fa-chart-line"></i>
                <h3>Улучшение качества найма</h3>
                <p>Наши алгоритмы помогают находить кандидатов с лучшим соответствием требованиям</p>
            </div>
        </div>
    </div>

    <!-- Секция с цифрами статистики -->
    <div class="stats-section">
        <h2>FlowHR в цифрах</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">1500+</div>
                <div class="stat-label">активных вакансий</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">25 000+</div>
                <div class="stat-label">кандидатов</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">98%</div>
                <div class="stat-label">положительных отзывов</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">500+</div>
                <div class="stat-label">компаний-партнеров</div>
            </div>
        </div>
    </div>

    <!-- Секция с процессом работы -->
    <div class="process-section">
        <h2>Как это работает</h2>
        <div class="process-grid">
            <div class="process-card">
                <div class="process-number">1</div>
                <i class="fas fa-user-plus"></i>
                <h3>Регистрация</h3>
                <p>Создайте аккаунт и заполните профиль с вашими данными и предпочтениями</p>
            </div>
            <div class="process-card">
                <div class="process-number">2</div>
                <i class="fas fa-search"></i>
                <h3>Поиск</h3>
                <p>Найдите подходящие вакансии или кандидатов с помощью умного поиска</p>
            </div>
            <div class="process-card">
                <div class="process-number">3</div>
                <i class="fas fa-handshake"></i>
                <h3>Взаимодействие</h3>
                <p>Общайтесь с работодателями или кандидатами через встроенный чат</p>
            </div>
            <div class="process-card">
                <div class="process-number">4</div>
                <i class="fas fa-check-circle"></i>
                <h3>Результат</h3>
                <p>Получите работу мечты или наймите идеального сотрудника</p>
            </div>
        </div>
    </div>

    <!-- Секция с преимуществами для разных пользователей -->
    <div class="advantages-section">
        <h2>Преимущества для вас</h2>
        <div class="advantages-grid">
            <div class="advantage-card hr-advantage">
                <i class="fas fa-user-tie"></i>
                <h3>Для HR-специалистов</h3>
                <ul>
                    <li>Автоматизация рутинных задач</li>
                    <li>Умный поиск кандидатов</li>
                    <li>Аналитика и отчеты</li>
                    <li>Управление вакансиями</li>
                </ul>
            </div>
            <div class="advantage-card candidate-advantage">
                <i class="fas fa-user-graduate"></i>
                <h3>Для соискателей</h3>
                <ul>
                    <li>Персональные рекомендации</li>
                    <li>Прямое общение с работодателями</li>
                    <li>Отслеживание статуса откликов</li>
                    <li>Уведомления о новых вакансиях</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>