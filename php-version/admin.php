<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Только админы могут видеть эту страницу
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

$pageTitle = 'Панель администратора - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();

// Статистика пользователей
$usersStats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
        COUNT(CASE WHEN role = 'manager' THEN 1 END) as managers,
        COUNT(CASE WHEN role = 'user' THEN 1 END) as users,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active,
        COUNT(CASE WHEN telegram_chat_id IS NOT NULL THEN 1 END) as with_telegram
    FROM users
")->fetch();

// Статистика задач
$tasksStats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        COUNT(CASE WHEN due_date < CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 END) as overdue,
        COUNT(CASE WHEN is_deleted = 1 THEN 1 END) as deleted
    FROM tasks
")->fetch();

// Статистика активности
$activityStats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM task_comments) as total_comments,
        (SELECT COUNT(*) FROM notifications) as total_notifications,
        (SELECT COUNT(*) FROM notifications WHERE is_read = 0) as unread_notifications,
        (SELECT COUNT(*) FROM telegram_messages) as telegram_messages,
        (SELECT COUNT(*) FROM sessions WHERE expires_at > NOW()) as active_sessions
")->fetch();

// Последние пользователи
$recentUsers = $db->query("
    SELECT id, email, full_name, role, is_active, telegram_chat_id, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

// Последняя активность
$recentActivity = $db->query("
    SELECT 
        'task_created' as type,
        t.id as item_id,
        t.title as item_title,
        u.full_name as user_name,
        t.created_at as activity_time
    FROM tasks t
    JOIN users u ON t.created_by = u.id
    WHERE t.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'comment_added' as type,
        c.task_id as item_id,
        t.title as item_title,
        u.full_name as user_name,
        c.created_at as activity_time
    FROM task_comments c
    JOIN tasks t ON c.task_id = t.id
    JOIN users u ON c.user_id = u.id
    WHERE c.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY activity_time DESC
    LIMIT 20
")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-shield-alt text-red-500"></i>
            Панель администратора
        </h2>
        <p class="text-gray-600 mt-2">Управление системой и мониторинг</p>
    </div>
    
    <!-- Статистика пользователей -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-users"></i> Пользователи
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Всего</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $usersStats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Админы</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $usersStats['admins']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Менеджеры</p>
                <p class="text-2xl font-bold text-purple-600"><?php echo $usersStats['managers']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Юзеры</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $usersStats['users']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Активные</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $usersStats['active']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">С Telegram</p>
                <p class="text-2xl font-bold text-cyan-600"><?php echo $usersStats['with_telegram']; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Статистика задач -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-tasks"></i> Задачи
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Всего</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $tasksStats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Ожидают</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $tasksStats['pending']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">В работе</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $tasksStats['in_progress']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Выполнено</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $tasksStats['completed']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Отменено</p>
                <p class="text-2xl font-bold text-gray-600"><?php echo $tasksStats['cancelled']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Просрочено</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $tasksStats['overdue']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Удалено</p>
                <p class="text-2xl font-bold text-gray-400"><?php echo $tasksStats['deleted']; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Статистика активности -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-chart-line"></i> Активность
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Комментарии</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $activityStats['total_comments']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Уведомления</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $activityStats['total_notifications']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Непрочитано</p>
                <p class="text-2xl font-bold text-orange-600"><?php echo $activityStats['unread_notifications']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Telegram</p>
                <p class="text-2xl font-bold text-cyan-600"><?php echo $activityStats['telegram_messages']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Сессии</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $activityStats['active_sessions']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Последние пользователи -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-user-plus"></i> Последние пользователи
                </h3>
                <a href="/users.php" class="text-blue-500 hover:text-blue-600 text-sm">
                    Все пользователи →
                </a>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <?php foreach ($recentUsers as $user): ?>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php
                                $roleBadges = [
                                    'admin' => 'bg-red-100 text-red-800',
                                    'manager' => 'bg-purple-100 text-purple-800',
                                    'user' => 'bg-blue-100 text-blue-800'
                                ];
                                $badgeClass = $roleBadges[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                                <?php if ($user['telegram_chat_id']): ?>
                                    <i class="fab fa-telegram text-cyan-500" title="Telegram подключён"></i>
                                <?php endif; ?>
                                <?php if (!$user['is_active']): ?>
                                    <i class="fas fa-ban text-red-500" title="Неактивен"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Последняя активность -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-history"></i> Последняя активность
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                <?php echo $activity['type'] === 'task_created' ? 'bg-blue-100' : 'bg-green-100'; ?>">
                                <i class="<?php echo $activity['type'] === 'task_created' ? 'fas fa-plus text-blue-600' : 'fas fa-comment text-green-600'; ?> text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800">
                                    <span class="font-medium"><?php echo htmlspecialchars($activity['user_name']); ?></span>
                                    <?php if ($activity['type'] === 'task_created'): ?>
                                        создал задачу
                                    <?php else: ?>
                                        добавил комментарий к
                                    <?php endif; ?>
                                    <a href="/task.php?id=<?php echo $activity['item_id']; ?>" class="text-blue-500 hover:text-blue-600">
                                        "<?php echo htmlspecialchars(substr($activity['item_title'], 0, 40)); ?>..."
                                    </a>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="far fa-clock"></i>
                                    <?php 
                                    $time = new DateTime($activity['activity_time']);
                                    echo $time->format('d.m.Y H:i');
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Быстрые действия -->
    <div class="mt-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-semibold text-white mb-4">
            <i class="fas fa-bolt"></i> Быстрые действия
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="/users.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 text-white transition">
                <i class="fas fa-users text-2xl mb-2"></i>
                <p class="font-medium">Управление пользователями</p>
                <p class="text-sm text-white text-opacity-80">Добавить, редактировать, удалить</p>
            </a>
            <a href="/tasks.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 text-white transition">
                <i class="fas fa-tasks text-2xl mb-2"></i>
                <p class="font-medium">Все задачи</p>
                <p class="text-sm text-white text-opacity-80">Просмотр и управление</p>
            </a>
            <a href="/telegram-setup.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 text-white transition">
                <i class="fab fa-telegram text-2xl mb-2"></i>
                <p class="font-medium">Настройка Telegram</p>
                <p class="text-sm text-white text-opacity-80">Webhook и конфигурация</p>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
