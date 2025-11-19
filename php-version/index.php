<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Главная - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$statsQuery = "
    SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN due_date < CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 END) as overdue
    FROM tasks
    WHERE is_deleted = 0
";

if ($userRole === 'user') {
    $statsQuery .= " AND (created_by = ? OR assigned_to = ?)";
    $stmt = $db->prepare($statsQuery);
    $stmt->execute([$userId, $userId]);
} else {
    $stmt = $db->query($statsQuery);
}

$stats = $stmt->fetch();

$recentTasksQuery = "
    SELECT 
        t.*,
        creator.full_name AS creator_name,
        assignee.full_name AS assignee_name
    FROM tasks t
    JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE t.is_deleted = 0
";

if ($userRole === 'user') {
    $recentTasksQuery .= " AND (t.created_by = ? OR t.assigned_to = ?)";
}

$recentTasksQuery .= " ORDER BY t.created_at DESC LIMIT 5";

if ($userRole === 'user') {
    $stmt = $db->prepare($recentTasksQuery);
    $stmt->execute([$userId, $userId]);
} else {
    $stmt = $db->query($recentTasksQuery);
}

$recentTasks = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
        </h2>
        <p class="text-gray-600 mt-2">Вот обзор ваших задач</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Ожидают</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['pending']; ?></p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">В работе</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['in_progress']; ?></p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-spinner text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Выполнено</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['completed']; ?></p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Просрочено</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['overdue']; ?></p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-800">Последние задачи</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($recentTasks)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-5xl mb-4"></i>
                            <p>Пока нет задач</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentTasks as $task): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <?php
                                                $priorityColors = [
                                                    'urgent' => 'text-red-500',
                                                    'high' => 'text-orange-500',
                                                    'medium' => 'text-yellow-500',
                                                    'low' => 'text-green-500'
                                                ];
                                                $priorityColor = $priorityColors[$task['priority']] ?? 'text-gray-500';
                                                ?>
                                                <i class="fas fa-circle <?php echo $priorityColor; ?> text-xs"></i>
                                                <h4 class="text-lg font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </h4>
                                            </div>
                                            
                                            <?php if ($task['description']): ?>
                                                <p class="text-gray-600 text-sm mb-2">
                                                    <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>
                                                    <?php if (strlen($task['description']) > 100) echo '...'; ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                                <?php if ($task['assignee_name']): ?>
                                                    <span>
                                                        <i class="fas fa-user"></i>
                                                        <?php echo htmlspecialchars($task['assignee_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($task['due_date']): ?>
                                                    <span>
                                                        <i class="fas fa-calendar"></i>
                                                        <?php 
                                                        $dueDate = new DateTime($task['due_date']);
                                                        echo $dueDate->format('d.m.Y');
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <?php
                                            $statusBadges = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $statusNames = [
                                                'pending' => 'Ожидает',
                                                'in_progress' => 'В работе',
                                                'completed' => 'Выполнено',
                                                'cancelled' => 'Отменено'
                                            ];
                                            $badgeClass = $statusBadges[$task['status']] ?? 'bg-gray-100 text-gray-800';
                                            $statusName = $statusNames[$task['status']] ?? $task['status'];
                                            ?>
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                                                <?php echo $statusName; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 flex gap-2">
                                        <a href="/task.php?id=<?php echo $task['id']; ?>" class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                                            Подробнее →
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 text-center">
                            <a href="/tasks.php" class="text-blue-500 hover:text-blue-600 font-medium">
                                Посмотреть все задачи →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Быстрые действия</h3>
                <div class="space-y-3">
                    <a href="/task-create.php" class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-3 rounded-lg font-medium transition">
                        <i class="fas fa-plus mr-2"></i>
                        Создать задачу
                    </a>
                    
                    <a href="/tasks.php?status=pending" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-center py-3 rounded-lg font-medium transition">
                        <i class="fas fa-list mr-2"></i>
                        Ожидающие задачи
                    </a>
                    
                    <a href="/my-tasks.php" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-center py-3 rounded-lg font-medium transition">
                        <i class="fas fa-user-check mr-2"></i>
                        Мои задачи
                    </a>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg shadow p-6 text-white">
                <h3 class="text-lg font-semibold mb-2">
                    <i class="fab fa-telegram text-2xl"></i>
                    Telegram уведомления
                </h3>
                <p class="text-sm opacity-90 mb-4">
                    Получайте уведомления о задачах прямо в Telegram
                </p>
                <a href="/profile.php" class="block w-full bg-white text-blue-600 text-center py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                    Настроить
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
