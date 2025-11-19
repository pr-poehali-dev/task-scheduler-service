<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Профиль - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-3xl font-bold text-gray-800 mb-8">Профиль пользователя</h2>
    
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white text-3xl font-bold mr-6">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <h3 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="text-sm text-gray-500 mt-1">
                    Роль: <span class="font-medium"><?php echo htmlspecialchars($user['role']); ?></span>
                </p>
            </div>
        </div>
        
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Дата регистрации</h4>
                <p class="text-gray-600">
                    <?php 
                    $createdAt = new DateTime($user['created_at']);
                    echo $createdAt->format('d.m.Y H:i');
                    ?>
                </p>
            </div>
            
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Последнее обновление</h4>
                <p class="text-gray-600">
                    <?php 
                    $updatedAt = new DateTime($user['updated_at']);
                    echo $updatedAt->format('d.m.Y H:i');
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-6">
            <i class="fab fa-telegram text-blue-500 mr-2"></i>
            Telegram уведомления
        </h3>
        
        <?php if ($user['telegram_chat_id']): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-800 font-medium">✅ Telegram подключен</p>
                        <?php if ($user['telegram_username']): ?>
                            <p class="text-sm text-green-600 mt-1">@<?php echo htmlspecialchars($user['telegram_username']); ?></p>
                        <?php endif; ?>
                    </div>
                    <button onclick="unlinkTelegram()" class="text-red-500 hover:text-red-600 text-sm font-medium">
                        Отключить
                    </button>
                </div>
            </div>
            
            <p class="text-gray-600 text-sm">
                Вы получаете уведомления о новых задачах прямо в Telegram.
            </p>
        <?php else: ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <p class="text-blue-800 font-medium mb-2">Подключите Telegram для уведомлений</p>
                <p class="text-sm text-blue-600 mb-3">
                    Получайте мгновенные уведомления о новых задачах в Telegram
                </p>
                
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                    <p class="text-sm font-medium text-gray-700 mb-2">Инструкция:</p>
                    <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                        <li>Откройте Telegram и найдите бота: <code class="bg-gray-100 px-2 py-1 rounded">@your_bot_username</code></li>
                        <li>Нажмите <strong>Start</strong></li>
                        <li>Отправьте команду: <code class="bg-gray-100 px-2 py-1 rounded">/start <?php echo htmlspecialchars($user['email']); ?></code></li>
                        <li>Готово! Обновите эту страницу</li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-lg shadow p-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-6">Настройки аккаунта</h3>
        
        <form id="updateProfileForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Полное имя
                </label>
                <input 
                    type="text" 
                    id="full_name" 
                    value="<?php echo htmlspecialchars($user['full_name']); ?>"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email (не изменяется)
                </label>
                <input 
                    type="email" 
                    value="<?php echo htmlspecialchars($user['email']); ?>"
                    disabled
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600"
                >
            </div>
            
            <div class="pt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>
                    Сохранить изменения
                </button>
            </div>
        </form>
        
        <hr class="my-8">
        
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Изменить пароль</h4>
        
        <form id="changePasswordForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Текущий пароль
                </label>
                <input 
                    type="password" 
                    id="current_password" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Новый пароль
                </label>
                <input 
                    type="password" 
                    id="new_password" 
                    required
                    minlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Подтвердите новый пароль
                </label>
                <input 
                    type="password" 
                    id="new_password_confirm" 
                    required
                    minlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <div class="pt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-key mr-2"></i>
                    Изменить пароль
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('updateProfileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    showNotification('Функция в разработке', 'info');
});

document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    showNotification('Функция в разработке', 'info');
});

function unlinkTelegram() {
    if (!confirm('Отключить Telegram уведомления?')) {
        return;
    }
    showNotification('Функция в разработке', 'info');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
