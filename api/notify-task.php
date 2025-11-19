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
    echo json_encode(['message' => 'Bot not configured, skipping notification']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$taskId = $input['taskId'] ?? null;
$taskTitle = $input['title'] ?? null;
$deadline = $input['deadline'] ?? 'Не указан';
$urgent = $input['urgent'] ?? false;
$createdBy = $input['createdBy'] ?? 'Администратор';
$assignedTo = $input['assignedTo'] ?? [];

if (!$taskId || !$taskTitle || empty($assignedTo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $db = getDB();
    $notificationsSent = 0;
    
    foreach ($assignedTo as $userName) {
        $stmt = $db->prepare(
            "SELECT telegram_chat_id, email FROM users WHERE full_name = ? AND telegram_chat_id IS NOT NULL"
        );
        $stmt->execute([$userName]);
        $user = $stmt->fetch();
        
        if ($user) {
            $chatId = $user['telegram_chat_id'];
            
            $urgentEmoji = $urgent ? '🔥 ' : '';
            $messageText = 
                "{$urgentEmoji}<b>Новая задача для выполнения</b>\n\n" .
                "📋 <b>Название:</b> {$taskTitle}\n" .
                "📅 <b>Срок:</b> {$deadline}\n" .
                "📊 <b>Статус:</b> В работе\n" .
                "👤 <b>От кого:</b> {$createdBy}\n";
            
            $replyMarkup = [
                'inline_keyboard' => [[
                    ['text' => '✅ Отметить выполненной', 'callback_data' => "complete_{$taskId}"]
                ]]
            ];
            
            sendTelegramMessage($botToken, $chatId, $messageText, $replyMarkup);
            $notificationsSent++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications_sent' => $notificationsSent
    ]);
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
