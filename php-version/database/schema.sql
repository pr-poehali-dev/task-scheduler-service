-- TaskFlow MySQL Database Schema
-- Версия: 1.0
-- Дата: 2025-01-18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS `taskflow_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `taskflow_db`;

-- ==========================================
-- Таблица: users (Пользователи)
-- ==========================================

CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
  `telegram_chat_id` BIGINT(20) DEFAULT NULL,
  `telegram_username` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `telegram_chat_id` (`telegram_chat_id`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Таблица: tasks (Задачи)
-- ==========================================

CREATE TABLE `tasks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `due_date` DATE DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Таблица: task_assignments (История назначений)
-- ==========================================

CREATE TABLE `task_assignments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `assigned_by` INT(11) NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_assignments_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Таблица: task_comments (Комментарии к задачам)
-- ==========================================

CREATE TABLE `task_comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_comments_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Таблица: notifications (Уведомления)
-- ==========================================

CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `task_id` INT(11) DEFAULT NULL,
  `type` ENUM('task_assigned', 'task_updated', 'task_completed', 'task_overdue', 'comment_added') NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_sent_telegram` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_sent_telegram` (`is_sent_telegram`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Таблица: telegram_messages (Логи сообщений Telegram)
-- ==========================================

CREATE TABLE `telegram_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chat_id` BIGINT(20) NOT NULL,
  `message_id` BIGINT(20) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `message_text` TEXT DEFAULT NULL,
  `message_type` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_telegram_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Таблица: sessions (Сессии пользователей)
-- ==========================================

CREATE TABLE `sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Вставка тестовых данных
-- ==========================================

-- Пользователь: admin (пароль: admin123)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('admin@taskflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', 'admin', 1);

-- Пользователь: manager (пароль: manager123)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('manager@taskflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Менеджер Проектов', 'manager', 1);

-- Пользователь: user (пароль: user123)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('user@taskflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Иван Иванов', 'user', 1);

-- Тестовые задачи
INSERT INTO `tasks` (`title`, `description`, `status`, `priority`, `due_date`, `created_by`, `assigned_to`) VALUES
('Настроить проект TaskFlow', 'Установить и настроить систему управления задачами', 'in_progress', 'high', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1, 2),
('Протестировать Telegram бота', 'Проверить работу уведомлений через Telegram', 'pending', 'medium', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 1, 3),
('Написать документацию', 'Создать руководство пользователя', 'pending', 'low', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 2, 3);

-- ==========================================
-- Индексы для оптимизации
-- ==========================================

-- Полнотекстовый поиск по задачам (требует MySQL 5.6+)
ALTER TABLE `tasks` ADD FULLTEXT KEY `ft_title_description` (`title`, `description`);

-- ==========================================
-- Представления (Views)
-- ==========================================

-- Представление: активные задачи с информацией о пользователях
CREATE OR REPLACE VIEW `v_active_tasks` AS
SELECT 
    t.id,
    t.title,
    t.description,
    t.status,
    t.priority,
    t.due_date,
    t.created_at,
    t.updated_at,
    creator.id AS creator_id,
    creator.full_name AS creator_name,
    creator.email AS creator_email,
    assignee.id AS assignee_id,
    assignee.full_name AS assignee_name,
    assignee.email AS assignee_email,
    assignee.telegram_chat_id AS assignee_telegram,
    CASE 
        WHEN t.due_date < CURDATE() THEN 'overdue'
        WHEN t.due_date = CURDATE() THEN 'today'
        WHEN t.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'soon'
        ELSE 'normal'
    END AS urgency_status
FROM tasks t
INNER JOIN users creator ON t.created_by = creator.id
LEFT JOIN users assignee ON t.assigned_to = assignee.id
WHERE t.is_deleted = 0 AND t.status != 'completed';

-- Представление: статистика по пользователям
CREATE OR REPLACE VIEW `v_user_stats` AS
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.role,
    COUNT(DISTINCT t_created.id) AS tasks_created,
    COUNT(DISTINCT t_assigned.id) AS tasks_assigned,
    COUNT(DISTINCT CASE WHEN t_assigned.status = 'completed' THEN t_assigned.id END) AS tasks_completed,
    COUNT(DISTINCT CASE WHEN t_assigned.status = 'in_progress' THEN t_assigned.id END) AS tasks_in_progress,
    COUNT(DISTINCT CASE WHEN t_assigned.due_date < CURDATE() AND t_assigned.status != 'completed' THEN t_assigned.id END) AS tasks_overdue
FROM users u
LEFT JOIN tasks t_created ON u.id = t_created.created_by AND t_created.is_deleted = 0
LEFT JOIN tasks t_assigned ON u.id = t_assigned.assigned_to AND t_assigned.is_deleted = 0
GROUP BY u.id;

-- ==========================================
-- Хранимые процедуры
-- ==========================================

DELIMITER //

-- Процедура: Назначить задачу пользователю
CREATE PROCEDURE sp_assign_task(
    IN p_task_id INT,
    IN p_user_id INT,
    IN p_assigned_by INT
)
BEGIN
    DECLARE v_old_assignee INT;
    
    -- Получаем текущего исполнителя
    SELECT assigned_to INTO v_old_assignee FROM tasks WHERE id = p_task_id;
    
    -- Закрываем предыдущее назначение если было
    IF v_old_assignee IS NOT NULL THEN
        UPDATE task_assignments 
        SET unassigned_at = NOW() 
        WHERE task_id = p_task_id AND user_id = v_old_assignee AND unassigned_at IS NULL;
    END IF;
    
    -- Обновляем задачу
    UPDATE tasks SET assigned_to = p_user_id WHERE id = p_task_id;
    
    -- Создаем запись о назначении
    INSERT INTO task_assignments (task_id, user_id, assigned_by) 
    VALUES (p_task_id, p_user_id, p_assigned_by);
    
    -- Создаем уведомление
    INSERT INTO notifications (user_id, task_id, type, message)
    SELECT p_user_id, p_task_id, 'task_assigned', 
           CONCAT('Вам назначена задача: ', title) 
    FROM tasks WHERE id = p_task_id;
END //

-- Процедура: Завершить задачу
CREATE PROCEDURE sp_complete_task(
    IN p_task_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE tasks 
    SET status = 'completed', completed_at = NOW() 
    WHERE id = p_task_id;
    
    -- Уведомляем создателя
    INSERT INTO notifications (user_id, task_id, type, message)
    SELECT created_by, p_task_id, 'task_completed',
           CONCAT('Задача завершена: ', title)
    FROM tasks WHERE id = p_task_id;
END //

-- Процедура: Очистка старых сессий
CREATE PROCEDURE sp_cleanup_sessions()
BEGIN
    DELETE FROM sessions WHERE expires_at < NOW();
END //

DELIMITER ;

-- ==========================================
-- События (Events) для автоматизации
-- ==========================================

-- Включаем планировщик событий
SET GLOBAL event_scheduler = ON;

-- Событие: Очистка старых сессий каждый час
CREATE EVENT IF NOT EXISTS evt_cleanup_sessions
ON SCHEDULE EVERY 1 HOUR
DO CALL sp_cleanup_sessions();

-- Событие: Отправка уведомлений о просроченных задачах каждый день в 9:00
CREATE EVENT IF NOT EXISTS evt_overdue_tasks_notification
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 9 HOUR)
DO
    INSERT INTO notifications (user_id, task_id, type, message)
    SELECT 
        t.assigned_to,
        t.id,
        'task_overdue',
        CONCAT('Задача просрочена: ', t.title)
    FROM tasks t
    WHERE t.assigned_to IS NOT NULL 
      AND t.due_date < CURDATE()
      AND t.status NOT IN ('completed', 'cancelled')
      AND t.is_deleted = 0
      AND NOT EXISTS (
          SELECT 1 FROM notifications n 
          WHERE n.task_id = t.id 
            AND n.type = 'task_overdue' 
            AND DATE(n.created_at) = CURDATE()
      );

COMMIT;

-- ==========================================
-- Информация о схеме
-- ==========================================

SELECT 'TaskFlow Database Schema v1.0 установлена успешно!' AS status;
SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = 'taskflow_db';
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_tasks FROM tasks;
