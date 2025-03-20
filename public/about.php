<?php
require '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - FlowHR</title>
    <!-- Подключаем Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Общие стили */
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4; /* Светлый фон */
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

            .header h1 {
                font-size: 28px; /* Уменьшаем размер заголовка */
            }

            .header p {
                font-size: 16px; /* Уменьшаем размер текста */
            }

            .about-section h2,
            .benefits-section h2,
            .testimonials-section h2,
            .partners-section h2,
            .team-section h2 {
                font-size: 24px; /* Уменьшаем размер заголовков */
            }

            .about-section p,
            .benefit-card p,
            .testimonial-card p,
            .team-member p {
                font-size: 14px; /* Уменьшаем размер текста */
            }

            .benefits-grid,
            .testimonials-grid,
            .team-grid {
                display: flex;
                overflow-x: auto; /* Горизонтальная прокрутка */
                gap: 20px;
                padding-bottom: 20px; /* Отступ для скролла */
                scroll-snap-type: x mandatory; /* Плавная прокрутка */
            }

            .benefit-card,
            .testimonial-card,
            .team-member {
                flex: 0 0 auto; /* Карточки не сжимаются */
                width: 80%; /* Ширина карточки */
                scroll-snap-align: start; /* Плавная прокрутка */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                border-radius: 10px;
                padding: 15px;
                background: white;
                border: 1px solid #E0E0E0;
            }

            .testimonial-card img,
            .team-member img {
                width: 60px; /* Уменьшаем размер изображений */
                height: 60px;
            }

            .tech-icons i,
            .social-links a {
                font-size: 16px; /* Уменьшаем размер иконок */
            }

            .contact-button a {
                padding: 10px 20px; /* Уменьшаем размер кнопки */
                font-size: 14px;
            }
        }

        /* Остальные стили остаются без изменений */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding: 50px 0;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            color: #FF6F61; /* Коралловый цвет */
        }

        .header p {
            font-size: 18px;
            margin: 0;
            color: #2C3E50; /* Темно-серый текст */
        }

        .about-section {
            padding: 50px 0;
            text-align: center;
        }

        .about-section h2 {
            font-size: 28px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 20px;
        }

        .about-section p {
            font-size: 16px;
            max-width: 800px;
            margin: 0 auto;
            color: #2C3E50; /* Темно-серый текст */
        }

        .benefits-section {
            padding: 50px 0;
            background: white; /* Белый фон */
            text-align: center;
        }

        .benefits-section h2 {
            font-size: 28px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 40px;
        }

        .benefits-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .benefit-card {
            background: #f4f4f4; /* Светлый фон */
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень */
            transition: transform 0.3s ease-in-out;
        }

        .benefit-card:hover {
            transform: translateY(-10px); /* Эффект поднятия */
        }

        .benefit-card i {
            font-size: 40px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 15px;
        }

        .benefit-card h3 {
            font-size: 20px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .benefit-card p {
            font-size: 14px;
            color: #666666; /* Серый текст */
        }

        .testimonials-section {
            padding: 50px 0;
            text-align: center;
        }

        .testimonials-section h2 {
            font-size: 28px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 40px;
        }

        .testimonials-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .testimonial-card {
            background: white; /* Белый фон */
            border-radius: 10px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень */
            transition: transform 0.3s ease-in-out;
        }

        .testimonial-card:hover {
            transform: translateY(-10px); /* Эффект поднятия */
        }

        .testimonial-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%; /* Круглая фотография */
            margin-bottom: 15px;
        }

        .testimonial-card h3 {
            font-size: 18px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .testimonial-card p {
            font-size: 14px;
            color: #666666; /* Серый текст */
            font-style: italic;
        }

        .team-section {
            padding: 50px 0;
            background: white; /* Белый фон */
            text-align: center;
        }

        .team-section h2 {
            font-size: 28px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 40px;
        }

        .team-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .team-member {
            background: #f4f4f4; /* Светлый фон */
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень */
            transition: transform 0.3s ease-in-out;
        }

        .team-member:hover {
            transform: translateY(-10px); /* Эффект поднятия */
        }

        .team-member img {
            width: 100px;
            height: 100px;
            border-radius: 50%; /* Круглая фотография */
            margin-bottom: 15px;
        }

        .team-member h3 {
            font-size: 20px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .team-member p {
            font-size: 14px;
            color: #666666; /* Серый текст */
        }

        .tech-icons {
            margin-top: 10px;
        }

        .tech-icons i {
            font-size: 20px;
            margin: 0 5px;
            color: #FF6F61; /* Коралловый цвет */
            transition: color 0.3s ease-in-out;
        }

        .tech-icons i:hover {
            color: #2C3E50; /* Темно-серый цвет */
        }

        .social-links {
            margin-top: 10px;
        }

        .social-links a {
            color: #FF6F61; /* Коралловый цвет */
            text-decoration: none;
            font-size: 18px;
            margin: 0 5px;
            transition: color 0.3s ease-in-out;
        }

        .social-links a:hover {
            color: #2C3E50; /* Темно-серый цвет */
        }

        .contact-button {
            text-align: center;
            margin: 50px 0;
        }

        .contact-button a {
            background: #FF6F61; /* Коралловый цвет */
            color: white; /* Белый текст */
            padding: 15px 30px;
            border-radius: 8px; /* Закругленные углы */
            text-decoration: none;
            font-size: 16px;
            transition: background 0.3s ease-in-out;
        }

        .contact-button a:hover {
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

        .about-section, .benefits-section, .testimonials-section, .team-section {
            opacity: 0;
            animation: fadeInUp 0.5s ease-in-out forwards;
        }

        .about-section {
            animation-delay: 0.2s;
        }

        .benefits-section {
            animation-delay: 0.4s;
        }

        .testimonials-section {
            animation-delay: 0.6s;
        }

        .team-section {
            animation-delay: 1s;
        }
    </style>
</head>
<body>
    <!-- Заголовок -->
    <div class="header">
        <h1>FlowHR</h1>
        <p>Сервис для HR-специалистов</p>
    </div>

    <!-- О компании -->
    <div class="container">
        <div class="about-section">
            <h2>О компании</h2>
            <p>
                FlowHR — это современный сервис, созданный для упрощения работы HR-специалистов. 
                Мы помогаем находить лучших кандидатов, управлять вакансиями и оптимизировать процессы подбора персонала. 
                Наша миссия — сделать HR-процессы максимально эффективными и удобными.
            </p>
        </div>

        <!-- Ключевые преимущества -->
        <div class="benefits-section">
            <h2>Наши преимущества</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <i class="fas fa-users"></i>
                    <h3>Широкая база кандидатов</h3>
                    <p>Доступ к тысячам профилей кандидатов по всей стране.</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Аналитика и отчеты</h3>
                    <p>Подробная аналитика по всем этапам подбора персонала.</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-clock"></i>
                    <h3>Экономия времени</h3>
                    <p>Автоматизация рутинных процессов для HR-специалистов.</p>
                </div>
            </div>
        </div>

        <!-- Отзывы клиентов -->
        <div class="testimonials-section">
            <h2>Отзывы наших клиентов</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <img src="../assets/images/users/1.jpg" alt="Фото клиента">
                    <h3>Гора Алексей</h3>
                    <p>"FlowHR помог нам сократить время подбора персонала на 30%. Очень довольны результатом!"</p>
                </div>
                <div class="testimonial-card">
                    <img src="../assets/images/users/2.jpg" alt="Фото клиента">
                    <h3>Черман Алексей</h3>
                    <p>"Отличный сервис! Удобный интерфейс и мощные инструменты для работы с кандидатами."</p>
                </div>
                <div class="testimonial-card">
                    <img src="../assets/images/users/3.jpg" alt="Фото клиента">
                    <h3>Сотников Даниил</h3>
                    <p>"Спасибо за качественный сервис. Теперь мы можем сосредоточиться на стратегических задачах."</p>
                </div>
            </div>
        </div>

        <!-- Команда разработчиков -->
        <div class="team-section">
            <h2>Наша команда</h2>
            <div class="team-grid">
                <!-- Разработчик 1 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Frontend-разработчик</p>
                    <div class="tech-icons">
                        <i class="fab fa-html5" title="HTML5"></i>
                        <i class="fab fa-css3-alt" title="CSS3"></i>
                        <i class="fab fa-js" title="JavaScript"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>

                <!-- Разработчик 2 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Backend-разработчик</p>
                    <div class="tech-icons">
                        <i class="fab fa-php" title="PHP"></i>
                        <i class="fab fa-laravel" title="Laravel"></i>
                        <i class="fab fa-database" title="SQL"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>

                <!-- Разработчик 3 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Дизайнер</p>
                    <div class="tech-icons">
                        <i class="fab fa-figma" title="Figma"></i>
                        <i class="fab fa-sketch" title="Sketch"></i>
                        <i class="fas fa-palette" title="UI/UX Design"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>

                <!-- Разработчик 4 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Тестировщик</p>
                    <div class="tech-icons">
                        <i class="fas fa-bug" title="Testing"></i>
                        <i class="fab fa-jira" title="Jira"></i>
                        <i class="fas fa-check-circle" title="QA"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>