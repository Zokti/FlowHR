<h1 align="center">
  <img src="https://via.placeholder.com/150x50/007bff/ffffff?text=FlowHR" alt="FlowHR Logo">
  <br>🚀 FlowHR - HR-автоматизация нового поколения
</h1>

<p align="center">
  <strong>Управление талантами, аналитика и автоматизация HR-процессов</strong>
</p>

<p align="center">
  <!-- Бейджи технологий -->
  <img src="https://img.shields.io/badge/PHP-7.2%2B-777BB4?logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="License">
  <img src="https://img.shields.io/badge/status-active-brightgreen" alt="Status">
</p>

<p align="center">
  <img src="https://via.placeholder.com/800x400/007bff/ffffff?text=FlowHR+Dashboard+Preview" alt="Preview" width="80%">
</p>

## 🌟 Ключевые возможности

### 👩‍💼 Для HR-специалистов
- **Управление вакансиями** - создание, публикация, архивирование
- **Автоматический подбор персонала** - интеллектуальное сопоставление кандидатов с вакансиями
- **Аналитика резюме** - автоматический анализ и ранжирование CV
- **📊 Расширенная аналитика** - метрики эффективности вакансий
- **💬 Встроенный мессенджер** - коммуникация с кандидатами

### 👤 Для кандидатов
- **Поиск вакансий** - фильтры по категориям и требованиям
- **Отслеживание статуса** - история откликов в личном кабинете
- **Онлайн-тестирование** - встроенная система оценки навыков
- **💬 Чат с HR** - прямое общение с рекрутерами

### ⚙️ Для администраторов
- **Централизованное управление** - роли и права доступа
- **Модерация контента** - управление вакансиями и откликами
- **Системная аналитика** - мониторинг активности и производительности
- **Управление пользователями** - регистрация, верификация, блокировка

## 🚀 Быстрый старт

### Предварительные требования
- PHP 7.2+
- MySQL 5.7+
- Composer (для зависимостей)

```bash
# 1. Клонирование репозитория
git clone https://github.com/ваш_логин/FlowHR.git
cd FlowHR

# 2. Настройка базы данных
mysql -u root -p -e "CREATE DATABASE FlowHR;"
mysql -u root -p FlowHR < database/FlowHR.sql

# 3. Конфигурация (обновите параметры в config.php)
cp config.example.php config.php
nano config.php

# 4. Установка зависимостей (если есть)
composer install

# 5. Запуск (настроить веб-сервер на папку public)
php -S localhost:8000 -t public
