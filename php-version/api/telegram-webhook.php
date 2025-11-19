<?php
require_once __DIR__ . '/../config.php';

setCorsHeaders();

class TelegramBot {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function handleWebhook() {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        if (!$update) {
            jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        logError('Telegram webhook received', ['update' => $update]);
        
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
        
        jsonResponse(['ok' => true]);
    }
    
    private function handleMessage($message) {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $username = $message['from']['username'] ?? null;
        
        $this->logMessage($chatId, $message['message_id'], $text, 'text');
        
        if (strpos($text, '/start') === 0) {
            $this->handleStart($chatId, $text, $username);
            return;
        }
        
        if ($text === '/help') {
            $this->sendHelp($chatId);
            return;
        }
        
        if ($text === '/tasks') {
            $this->sendMyTasks($chatId);
            return;
        }
        
        if ($text === '/unlink') {
            $this->unlinkAccount($chatId);
            return;
        }
        
        $this->sendMessage($chatId, "Неизвестная команда. Используйте /help для списка команд.");
    }
    
    private function handleStart($chatId, $text, $username) {
        $parts = explode(' ', $text);
        
        if (count($parts) < 2) {
            $this->sendMessage(
                $chatId, 
                "👋 Привет! Я бот TaskFlow для уведомлений о задачах.\n\n" .
                "Чтобы привязать аккаунт, используйте команду:\n" .
                "/start ВАШ_EMAIL\n\n" .
                "Например: /start user@example.com"
            );
            return;
        }
        
        $email = trim($parts[1]);
        
        if (!isValidEmail($email)) {
            $this->sendMessage($chatId, "❌ Неверный формат email. Попробуйте снова.");
            return;
        }
        
        $stmt = $this->db->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $this->sendMessage($chatId, "❌ Пользователь с email {$email} не найден в системе.");
            return;
        }
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET telegram_chat_id = ?, telegram_username = ? 
            WHERE id = ?
        ");
        $stmt->execute([$chatId, $username, $user['id']]);
        
        $this->sendMessage(
            $chatId,
            "✅ Отлично! Ваш Telegram привязан к аккаунту {$user['full_name']}.\n\n" .
            "Теперь вы будете получать уведомления о новых задачах.\n\n" .
            "Доступные команды:\n" .
            "/tasks - Мои активные задачи\n" .
            "/help - Помощь\n" .
            "/unlink - Отвязать аккаунт"
        );
    }
    
    private function handleCallbackQuery($callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        $messageId = $callbackQuery['message']['message_id'];
        
        if (strpos($data, 'complete_') === 0) {
            $taskId = str_replace('complete_', '', $data);
            $this->completeTask($chatId, $taskId, $messageId);
        }
        
        $this->answerCallbackQuery($callbackQuery['id']);
    }
    
    private function completeTask($chatId, $taskId, $messageId) {
        $stmt = $this->db->prepare("
            SELECT u.id 
            FROM users u 
            WHERE u.telegram_chat_id = ?
        ");
        $stmt->execute([$chatId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $this->sendMessage($chatId, "❌ Аккаунт не привязан. Используйте /start EMAIL");
            return;
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM tasks 
            WHERE id = ? AND assigned_to = ? AND is_deleted = 0
        ");
        $stmt->execute([$taskId, $user['id']]);
        $task = $stmt->fetch();
        
        if (!$task) {
            $this->sendMessage($chatId, "❌ Задача не найдена или вам не доступна.");
            return;
        }
        
        if ($task['status'] === 'completed') {
            $this->sendMessage($chatId, "ℹ️ Эта задача уже выполнена.");
            return;
        }
        
        $stmt = $this->db->prepare("
            UPDATE tasks 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, task_id, type, message)
            VALUES (?, ?, 'task_completed', ?)
        ");
        $stmt->execute([
            $task['created_by'],
            $taskId,
            "Задача выполнена: {$task['title']}"
        ]);
        
        $this->editMessageText(
            $chatId,
            $messageId,
            "✅ Задача выполнена!\n\n📝 {$task['title']}\n\n" .
            "Отмечена как завершённая " . date('d.m.Y H:i')
        );
    }
    
    private function sendMyTasks($chatId) {
        $stmt = $this->db->prepare("
            SELECT u.id 
            FROM users u 
            WHERE u.telegram_chat_id = ?
        ");
        $stmt->execute([$chatId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $this->sendMessage($chatId, "❌ Аккаунт не привязан. Используйте /start EMAIL");
            return;
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM tasks 
            WHERE assigned_to = ? 
              AND status != 'completed' 
              AND is_deleted = 0
            ORDER BY 
                CASE priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END,
                due_date ASC
            LIMIT 10
        ");
        $stmt->execute([$user['id']]);
        $tasks = $stmt->fetchAll();
        
        if (empty($tasks)) {
            $this->sendMessage($chatId, "📭 У вас нет активных задач. Отличная работа!");
            return;
        }
        
        $message = "📋 Ваши активные задачи (" . count($tasks) . "):\n\n";
        
        foreach ($tasks as $i => $task) {
            $num = $i + 1;
            $priority = [
                'low' => '🟢',
                'medium' => '🟡',
                'high' => '🟠',
                'urgent' => '🔴'
            ][$task['priority']] ?? '';
            
            $status = [
                'pending' => '⏳',
                'in_progress' => '🔄'
            ][$task['status']] ?? '📌';
            
            $message .= "{$num}. {$priority}{$status} {$task['title']}\n";
            
            if ($task['due_date']) {
                $dueDate = date('d.m.Y', strtotime($task['due_date']));
                $isOverdue = strtotime($task['due_date']) < strtotime('today');
                
                if ($isOverdue) {
                    $message .= "   ⚠️ Просрочено: {$dueDate}\n";
                } else {
                    $message .= "   📅 Срок: {$dueDate}\n";
                }
            }
            
            $message .= "\n";
        }
        
        $message .= "Используйте веб-интерфейс для подробностей: " . BASE_URL;
        
        $this->sendMessage($chatId, $message);
    }
    
    private function unlinkAccount($chatId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET telegram_chat_id = NULL, telegram_username = NULL 
            WHERE telegram_chat_id = ?
        ");
        $stmt->execute([$chatId]);
        
        if ($stmt->rowCount() > 0) {
            $this->sendMessage(
                $chatId,
                "✅ Ваш Telegram аккаунт успешно отвязан.\n\n" .
                "Для повторной привязки используйте: /start EMAIL"
            );
        } else {
            $this->sendMessage($chatId, "ℹ️ Аккаунт не был привязан.");
        }
    }
    
    private function sendHelp($chatId) {
        $message = "📖 Доступные команды:\n\n";
        $message .= "/start EMAIL - Привязать Telegram к аккаунту\n";
        $message .= "/tasks - Показать мои активные задачи\n";
        $message .= "/help - Показать это сообщение\n";
        $message .= "/unlink - Отвязать аккаунт\n\n";
        $message .= "🌐 Веб-интерфейс: " . BASE_URL;
        
        $this->sendMessage($chatId, $message);
    }
    
    private function sendMessage($chatId, $text, $replyMarkup = null) {
        $url = TELEGRAM_API_URL . '/sendMessage';
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }
        
        return $this->apiRequest($url, $data);
    }
    
    private function editMessageText($chatId, $messageId, $text) {
        $url = TELEGRAM_API_URL . '/editMessageText';
        
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        return $this->apiRequest($url, $data);
    }
    
    private function answerCallbackQuery($callbackQueryId, $text = null) {
        $url = TELEGRAM_API_URL . '/answerCallbackQuery';
        
        $data = ['callback_query_id' => $callbackQueryId];
        
        if ($text) {
            $data['text'] = $text;
        }
        
        return $this->apiRequest($url, $data);
    }
    
    private function apiRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200 || !$result['ok']) {
            logError('Telegram API error', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $result
            ]);
        }
        
        return $result;
    }
    
    private function logMessage($chatId, $messageId, $text, $type) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM users WHERE telegram_chat_id = ?
            ");
            $stmt->execute([$chatId]);
            $user = $stmt->fetch();
            
            $userId = $user ? $user['id'] : null;
            
            $stmt = $this->db->prepare("
                INSERT INTO telegram_messages (chat_id, message_id, user_id, message_text, message_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$chatId, $messageId, $userId, $text, $type]);
        } catch (Exception $e) {
            logError('Failed to log telegram message', ['error' => $e->getMessage()]);
        }
    }
    
    public function setWebhook() {
        $url = TELEGRAM_API_URL . '/setWebhook';
        
        $data = [
            'url' => WEBHOOK_URL,
            'allowed_updates' => ['message', 'callback_query']
        ];
        
        $response = $this->apiRequest($url, $data);
        
        jsonResponse($response);
    }
    
    public function getWebhookInfo() {
        $url = TELEGRAM_API_URL . '/getWebhookInfo';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        jsonResponse(json_decode($response, true));
    }
}

$bot = new TelegramBot();
$action = $_GET['action'] ?? 'webhook';

switch ($action) {
    case 'webhook':
        $bot->handleWebhook();
        break;
    case 'set':
        $bot->setWebhook();
        break;
    case 'info':
        $bot->getWebhookInfo();
        break;
    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
