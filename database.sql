-- TaskFlow Database Schema
-- Создание базы данных и таблиц

CREATE DATABASE IF NOT EXISTS taskflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taskflow;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    telegram_chat_id BIGINT DEFAULT NULL,
    avatar_url VARCHAR(512) DEFAULT NULL,
    tasks_completed INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_telegram (telegram_chat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица задач
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(512) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    deadline DATE DEFAULT NULL,
    created_by INT DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица заметок
CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    text TEXT NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_completed (completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка тестовых пользователей
-- Пароль для всех: admin123
INSERT INTO users (full_name, email, password_hash, role, tasks_completed) VALUES
('Алексей Иванов', 'alex@company.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 24),
('Мария Петрова', 'maria@company.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 18),
('Иван Сидоров', 'ivan@company.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 31),
('Елена Смирнова', 'elena@company.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 15),
('Дмитрий Козлов', 'dmitry@company.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 22);

-- Вставка тестовых задач
INSERT INTO tasks (title, description, priority, status, deadline, created_by, assigned_to) VALUES
('Подготовить презентацию для клиента', 'Создать презентацию с результатами работы за квартал', 'high', 'completed', '2025-01-20', 1, 1),
('Провести код-ревью PR #234', 'Проверить качество кода в pull request #234', 'medium', 'in_progress', '2025-01-18', 1, 1),
('Обновить документацию API', 'Добавить описание новых эндпоинтов в документацию', 'low', 'pending', '2025-01-25', 1, 2),
('Настроить CI/CD pipeline', 'Интегрировать автоматическое тестирование и деплой', 'high', 'in_progress', '2025-01-22', 1, 3),
('Провести встречу с командой', 'Обсудить планы на следующий спринт', 'medium', 'pending', '2025-01-19', 1, 4),
('Оптимизировать производительность БД', 'Добавить индексы и оптимизировать запросы', 'urgent', 'pending', '2025-01-17', 1, 3),
('Написать юнит-тесты', 'Покрыть новый функционал тестами', 'medium', 'pending', '2025-01-23', 1, 2),
('Обновить зависимости проекта', 'Проверить и обновить все npm пакеты', 'low', 'completed', '2025-01-15', 1, 5);

-- Вставка тестовых заметок
INSERT INTO notes (user_id, text, completed) VALUES
(1, 'Не забыть купить подарок коллеге', 0),
(1, 'Посмотреть вебинар по React 19', 1),
(1, 'Записаться к стоматологу', 0),
(2, 'Подготовить отчет для менеджера', 0),
(3, 'Обновить профиль на GitHub', 1);

-- Создание представления для статистики
CREATE OR REPLACE VIEW task_statistics AS
SELECT 
    u.id AS user_id,
    u.full_name,
    u.email,
    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) AS completed_tasks,
    COUNT(CASE WHEN t.status = 'pending' THEN 1 END) AS pending_tasks,
    COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) AS in_progress_tasks,
    COUNT(t.id) AS total_tasks
FROM users u
LEFT JOIN tasks t ON u.id = t.assigned_to AND t.is_deleted = 0
GROUP BY u.id, u.full_name, u.email;

-- Сообщение об успешном создании
SELECT 'База данных TaskFlow успешно создана и заполнена тестовыми данными!' AS status;
SELECT 'Тестовые аккаунты (пароль для всех: admin123):' AS info;
SELECT 
    email AS 'Email', 
    full_name AS 'Имя',
    role AS 'Роль'
FROM users
ORDER BY 
    CASE role 
        WHEN 'admin' THEN 1 
        WHEN 'manager' THEN 2 
        ELSE 3 
    END;
