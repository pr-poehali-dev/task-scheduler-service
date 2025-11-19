<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$botToken = TELEGRAM_BOT_TOKEN;
if (empty($botToken)) {
    http_response_code(500);
    echo json_encode(['error' => 'Bot token not configured']);
    exit();
}

$update = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDB();
    
    if (isset($update['message'])) {
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        if (strpos($text, '/start') === 0) {
            $parts = explode(' ', $text);
            
            if (count($parts) > 1) {
                $email = $parts[1];
                
                $stmt = $db->prepare("UPDATE users SET telegram_chat_id = ? WHERE email = ?");
                $stmt->execute([$chatId, $email]);
                
                $stmt = $db->prepare("SELECT full_name FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    sendTelegramMessage(
                        $botToken,
                        $chatId,
                        "✅ Привет, {$user['full_name']}!\n\n" .
                        "Вы успешно подключили Telegram уведомления.\n" .
                        "Теперь вы будете получать уведомления о новых задачах."
                    );
                } else {
                    sendTelegramMessage(
                        $botToken,
                        $chatId,
                        "❌ Ошибка: пользователь с таким email не найден."
                    );
                }
            } else {
                sendTelegramMessage(
                    $botToken,
                    $chatId,
                    "👋 Добро пожаловать в TaskFlow бот!\n\n" .
                    "Для подключения уведомлений используйте ссылку из профиля в веб-приложении."
                );
            }
        }
    } elseif (isset($update['callback_query'])) {
        $callback = $update['callback_query'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $data = $callback['data'];
        
        if (strpos($data, 'complete_') === 0) {
            $taskId = (int)substr($data, 9);
            
            $stmt = $db->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            $stmt = $db->prepare("SELECT title FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            
            if ($task) {
                editTelegramMessage(
                    $botToken,
                    $chatId,
                    $messageId,
                    "✅ <s>{$task['title']}</s>\n\n<b>Статус:</b> Выполнено ✓"
                );
                
                answerCallbackQuery($botToken, $callback['id'], "Задача отмечена выполненной!");
            }
        }
    }
    
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function sendTelegramMessage($token, $chatId, $text, $replyMarkup = null) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = json_encode($replyMarkup);
    }
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    file_get_contents($url, false, stream_context_create($options));
}

function editTelegramMessage($token, $chatId, $messageId, $text) {
    $url = "https://api.telegram.org/bot{$token}/editMessageText";
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    file_get_contents($url, false, stream_context_create($options));
}

function answerCallbackQuery($token, $callbackId, $text) {
    $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";
    $data = [
        'callback_query_id' => $callbackId,
        'text' => $text
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    file_get_contents($url, false, stream_context_create($options));
}
