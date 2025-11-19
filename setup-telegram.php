<?php
require_once 'config.php';

echo "🤖 Настройка Telegram бота для TaskFlow\n";
echo str_repeat('=', 50) . "\n\n";

if (empty(TELEGRAM_BOT_TOKEN)) {
    echo "❌ ОШИБКА: Токен бота не настроен!\n\n";
    echo "📝 Инструкция:\n";
    echo "1. Откройте Telegram и найдите @BotFather\n";
    echo "2. Отправьте команду /newbot\n";
    echo "3. Следуйте инструкциям и получите токен\n";
    echo "4. Откройте config.php и вставьте токен в TELEGRAM_BOT_TOKEN\n";
    echo "5. Запустите этот скрипт снова\n";
    exit(1);
}

$action = $_GET['action'] ?? 'info';

if ($action === 'set') {
    $webhookUrl = BASE_URL . '/api/telegram-webhook.php';
    
    echo "🔄 Устанавливаю webhook...\n";
    echo "URL: $webhookUrl\n\n";
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook";
    $data = ['url' => $webhookUrl];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    $response = file_get_contents($url, false, stream_context_create($options));
    $result = json_decode($response, true);
    
    if ($result['ok']) {
        echo "✅ Webhook успешно установлен!\n";
        echo "📱 Теперь пользователи могут подключить Telegram уведомления\n";
    } else {
        echo "❌ Ошибка установки webhook: " . $result['description'] . "\n";
    }
} elseif ($action === 'delete') {
    echo "🗑️  Удаляю webhook...\n\n";
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/deleteWebhook";
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    if ($result['ok']) {
        echo "✅ Webhook успешно удален!\n";
    } else {
        echo "❌ Ошибка удаления webhook\n";
    }
} elseif ($action === 'info') {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getWebhookInfo";
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    if ($result['ok']) {
        $info = $result['result'];
        
        echo "📊 Информация о боте:\n\n";
        
        if (empty($info['url'])) {
            echo "⚠️  Webhook не установлен\n\n";
            echo "Для установки webhook откройте:\n";
            echo BASE_URL . "/setup-telegram.php?action=set\n";
        } else {
            echo "✅ Webhook URL: " . $info['url'] . "\n";
            echo "📅 Последнее обновление: " . date('Y-m-d H:i:s', $info['last_error_date'] ?? time()) . "\n";
            
            if (!empty($info['last_error_message'])) {
                echo "❌ Последняя ошибка: " . $info['last_error_message'] . "\n";
            }
            
            echo "📩 Ожидающих обновлений: " . ($info['pending_update_count'] ?? 0) . "\n";
        }
        
        $botInfoUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getMe";
        $botResponse = file_get_contents($botInfoUrl);
        $botResult = json_decode($botResponse, true);
        
        if ($botResult['ok']) {
            $bot = $botResult['result'];
            echo "\n🤖 Информация о боте:\n";
            echo "Имя: @" . $bot['username'] . "\n";
            echo "ID: " . $bot['id'] . "\n";
        }
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "📚 Доступные команды:\n";
echo "?action=info   - Показать информацию о боте\n";
echo "?action=set    - Установить webhook\n";
echo "?action=delete - Удалить webhook\n";
