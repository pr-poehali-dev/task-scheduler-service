<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

setCorsHeaders();

class TasksAPI {
    private $db;
    private $currentUser;
    
    public function __construct() {
        $this->db = getDB();
        $this->currentUser = $this->authenticate();
    }
    
    private function authenticate() {
        $auth = new AuthAPI();
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            jsonResponse(['error' => 'Требуется авторизация'], 401);
        }
        
        if (preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
            $user = $auth->getUserByToken($token);
            
            if (!$user) {
                jsonResponse(['error' => 'Неверный или истекший токен'], 401);
            }
            
            return $user;
        }
        
        jsonResponse(['error' => 'Требуется авторизация'], 401);
    }
    
    public function getAllTasks() {
        $status = $_GET['status'] ?? null;
        $assignedTo = $_GET['assigned_to'] ?? null;
        $priority = $_GET['priority'] ?? null;
        
        $sql = "
            SELECT 
                t.*,
                creator.full_name AS creator_name,
                creator.email AS creator_email,
                assignee.full_name AS assignee_name,
                assignee.email AS assignee_email,
                assignee.telegram_chat_id AS assignee_telegram
            FROM tasks t
            JOIN users creator ON t.created_by = creator.id
            LEFT JOIN users assignee ON t.assigned_to = assignee.id
            WHERE t.is_deleted = 0
        ";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        if ($assignedTo) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $assignedTo;
        }
        
        if ($priority) {
            $sql .= " AND t.priority = ?";
            $params[] = $priority;
        }
        
        if ($this->currentUser['role'] === 'user') {
            $sql .= " AND (t.created_by = ? OR t.assigned_to = ?)";
            $params[] = $this->currentUser['id'];
            $params[] = $this->currentUser['id'];
        }
        
        $sql .= " ORDER BY 
            CASE t.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END,
            t.due_date ASC,
            t.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'tasks' => $tasks]);
    }
    
    public function getTask($id) {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                creator.full_name AS creator_name,
                creator.email AS creator_email,
                assignee.full_name AS assignee_name,
                assignee.email AS assignee_email,
                assignee.telegram_chat_id AS assignee_telegram
            FROM tasks t
            JOIN users creator ON t.created_by = creator.id
            LEFT JOIN users assignee ON t.assigned_to = assignee.id
            WHERE t.id = ? AND t.is_deleted = 0
        ");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        
        if (!$task) {
            jsonResponse(['error' => 'Задача не найдена'], 404);
        }
        
        if ($this->currentUser['role'] === 'user') {
            if ($task['created_by'] != $this->currentUser['id'] && 
                $task['assigned_to'] != $this->currentUser['id']) {
                jsonResponse(['error' => 'Доступ запрещен'], 403);
            }
        }
        
        $stmt = $this->db->prepare("
            SELECT c.*, u.full_name, u.email
            FROM task_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$id]);
        $comments = $stmt->fetchAll();
        
        $task['comments'] = $comments;
        
        jsonResponse(['success' => true, 'task' => $task]);
    }
    
    public function createTask() {
        $input = getJsonInput();
        
        if (!isset($input['title']) || empty(trim($input['title']))) {
            jsonResponse(['error' => 'Название задачи обязательно'], 400);
        }
        
        $title = trim($input['title']);
        $description = isset($input['description']) ? trim($input['description']) : null;
        $status = isset($input['status']) ? $input['status'] : 'pending';
        $priority = isset($input['priority']) ? $input['priority'] : 'medium';
        $dueDate = isset($input['due_date']) ? $input['due_date'] : null;
        $assignedTo = isset($input['assigned_to']) ? $input['assigned_to'] : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO tasks (title, description, status, priority, due_date, created_by, assigned_to)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        try {
            $this->db->beginTransaction();
            
            $stmt->execute([
                $title, 
                $description, 
                $status, 
                $priority, 
                $dueDate, 
                $this->currentUser['id'], 
                $assignedTo
            ]);
            
            $taskId = $this->db->lastInsertId();
            
            if ($assignedTo) {
                $stmt = $this->db->prepare("
                    INSERT INTO task_assignments (task_id, user_id, assigned_by)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$taskId, $assignedTo, $this->currentUser['id']]);
                
                $stmt = $this->db->prepare("
                    INSERT INTO notifications (user_id, task_id, type, message)
                    VALUES (?, ?, 'task_assigned', ?)
                ");
                $stmt->execute([
                    $assignedTo, 
                    $taskId, 
                    "Вам назначена задача: {$title}"
                ]);
                
                $this->sendTelegramNotification($assignedTo, $taskId, 'task_assigned');
            }
            
            $this->db->commit();
            
            $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            
            jsonResponse([
                'success' => true, 
                'message' => 'Задача создана', 
                'task' => $task
            ], 201);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            logError('Task creation error', ['error' => $e->getMessage()]);
            jsonResponse(['error' => 'Ошибка создания задачи'], 500);
        }
    }
    
    public function updateTask($id) {
        $input = getJsonInput();
        
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        
        if (!$task) {
            jsonResponse(['error' => 'Задача не найдена'], 404);
        }
        
        if ($this->currentUser['role'] === 'user' && 
            $task['created_by'] != $this->currentUser['id'] && 
            $task['assigned_to'] != $this->currentUser['id']) {
            jsonResponse(['error' => 'Доступ запрещен'], 403);
        }
        
        $fields = [];
        $params = [];
        
        if (isset($input['title'])) {
            $fields[] = "title = ?";
            $params[] = trim($input['title']);
        }
        
        if (isset($input['description'])) {
            $fields[] = "description = ?";
            $params[] = trim($input['description']);
        }
        
        if (isset($input['status'])) {
            $fields[] = "status = ?";
            $params[] = $input['status'];
            
            if ($input['status'] === 'completed') {
                $fields[] = "completed_at = NOW()";
            }
        }
        
        if (isset($input['priority'])) {
            $fields[] = "priority = ?";
            $params[] = $input['priority'];
        }
        
        if (isset($input['due_date'])) {
            $fields[] = "due_date = ?";
            $params[] = $input['due_date'];
        }
        
        if (isset($input['assigned_to'])) {
            $oldAssignee = $task['assigned_to'];
            $newAssignee = $input['assigned_to'];
            
            if ($oldAssignee != $newAssignee) {
                $fields[] = "assigned_to = ?";
                $params[] = $newAssignee;
                
                if ($oldAssignee) {
                    $stmt = $this->db->prepare("
                        UPDATE task_assignments 
                        SET unassigned_at = NOW() 
                        WHERE task_id = ? AND user_id = ? AND unassigned_at IS NULL
                    ");
                    $stmt->execute([$id, $oldAssignee]);
                }
                
                if ($newAssignee) {
                    $stmt = $this->db->prepare("
                        INSERT INTO task_assignments (task_id, user_id, assigned_by)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$id, $newAssignee, $this->currentUser['id']]);
                    
                    $this->sendTelegramNotification($newAssignee, $id, 'task_assigned');
                }
            }
        }
        
        if (empty($fields)) {
            jsonResponse(['error' => 'Нет полей для обновления'], 400);
        }
        
        $params[] = $id;
        $sql = "UPDATE tasks SET " . implode(", ", $fields) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if (isset($input['status']) && $input['status'] === 'completed') {
                $stmt = $this->db->prepare("
                    INSERT INTO notifications (user_id, task_id, type, message)
                    SELECT created_by, ?, 'task_completed', CONCAT('Задача завершена: ', title)
                    FROM tasks WHERE id = ?
                ");
                $stmt->execute([$id, $id]);
            }
            
            $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $updatedTask = $stmt->fetch();
            
            jsonResponse([
                'success' => true, 
                'message' => 'Задача обновлена', 
                'task' => $updatedTask
            ]);
            
        } catch (Exception $e) {
            logError('Task update error', ['error' => $e->getMessage()]);
            jsonResponse(['error' => 'Ошибка обновления задачи'], 500);
        }
    }
    
    public function deleteTask($id) {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        
        if (!$task) {
            jsonResponse(['error' => 'Задача не найдена'], 404);
        }
        
        if ($this->currentUser['role'] !== 'admin' && 
            $task['created_by'] != $this->currentUser['id']) {
            jsonResponse(['error' => 'Доступ запрещен'], 403);
        }
        
        $stmt = $this->db->prepare("UPDATE tasks SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(['success' => true, 'message' => 'Задача удалена']);
    }
    
    public function addComment($taskId) {
        $input = getJsonInput();
        
        if (!isset($input['comment']) || empty(trim($input['comment']))) {
            jsonResponse(['error' => 'Комментарий не может быть пустым'], 400);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            jsonResponse(['error' => 'Задача не найдена'], 404);
        }
        
        $comment = trim($input['comment']);
        
        $stmt = $this->db->prepare("
            INSERT INTO task_comments (task_id, user_id, comment)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$taskId, $this->currentUser['id'], $comment]);
        
        $commentId = $this->db->lastInsertId();
        
        $stmt = $this->db->prepare("
            SELECT c.*, u.full_name, u.email
            FROM task_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        $newComment = $stmt->fetch();
        
        jsonResponse([
            'success' => true, 
            'message' => 'Комментарий добавлен', 
            'comment' => $newComment
        ], 201);
    }
    
    private function sendTelegramNotification($userId, $taskId, $type) {
        $stmt = $this->db->prepare("
            SELECT telegram_chat_id FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['telegram_chat_id']) {
            return;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            return;
        }
        
        $message = $this->formatTelegramMessage($task, $type);
        
        $this->sendTelegramMessage($user['telegram_chat_id'], $message, $taskId);
    }
    
    private function formatTelegramMessage($task, $type) {
        $emoji = [
            'task_assigned' => '📋',
            'task_updated' => '🔄',
            'task_completed' => '✅',
            'task_overdue' => '⚠️'
        ];
        
        $priorityEmoji = [
            'low' => '🟢',
            'medium' => '🟡',
            'high' => '🟠',
            'urgent' => '🔴'
        ];
        
        $icon = $emoji[$type] ?? '📌';
        $priority = $priorityEmoji[$task['priority']] ?? '';
        
        $message = "{$icon} Новая задача\n\n";
        $message .= "📝 {$task['title']}\n";
        
        if ($task['description']) {
            $message .= "📄 " . substr($task['description'], 0, 100) . "\n";
        }
        
        $message .= "{$priority} Приоритет: {$task['priority']}\n";
        
        if ($task['due_date']) {
            $message .= "📅 Срок: {$task['due_date']}\n";
        }
        
        return $message;
    }
    
    private function sendTelegramMessage($chatId, $message, $taskId = null) {
        $url = TELEGRAM_API_URL . '/sendMessage';
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        if ($taskId) {
            $data['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    ['text' => '✅ Выполнено', 'callback_data' => "complete_{$taskId}"],
                    ['text' => '👁 Посмотреть', 'url' => BASE_URL . "/task.php?id={$taskId}"]
                ]]
            ]);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

$tasks = new TasksAPI();
$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

switch ($method) {
    case 'GET':
        if (empty($path[0])) {
            $tasks->getAllTasks();
        } else {
            $tasks->getTask($path[0]);
        }
        break;
        
    case 'POST':
        if (!empty($path[0]) && $path[0] !== '' && isset($path[1]) && $path[1] === 'comment') {
            $tasks->addComment($path[0]);
        } else {
            $tasks->createTask();
        }
        break;
        
    case 'PUT':
        if (empty($path[0])) {
            jsonResponse(['error' => 'ID задачи обязателен'], 400);
        }
        $tasks->updateTask($path[0]);
        break;
        
    case 'DELETE':
        if (empty($path[0])) {
            jsonResponse(['error' => 'ID задачи обязателен'], 400);
        }
        $tasks->deleteTask($path[0]);
        break;
        
    default:
        jsonResponse(['error' => 'Метод не поддерживается'], 405);
}
