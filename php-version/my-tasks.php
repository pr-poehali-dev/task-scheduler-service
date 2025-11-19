<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Мои задачи - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT 
        t.*,
        creator.full_name AS creator_name,
        creator.email AS creator_email
    FROM tasks t
    JOIN users creator ON t.created_by = creator.id
    WHERE t.assigned_to = ? AND t.is_deleted = 0
    ORDER BY 
        CASE t.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
        END,
        t.due_date ASC,
        t.created_at DESC
");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll();

$pendingTasks = array_filter($tasks, fn($t) => $t['status'] === 'pending');
$inProgressTasks = array_filter($tasks, fn($t) => $t['status'] === 'in_progress');
$completedTasks = array_filter($tasks, fn($t) => $t['status'] === 'completed');
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Мои задачи</h2>
        <p class="text-gray-600 mt-2">Всего назначено: <?php echo count($tasks); ?></p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Ожидают</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($pendingTasks); ?></p>
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
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($inProgressTasks); ?></p>
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
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($completedTasks); ?></p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($tasks)): ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-tasks text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">У вас пока нет задач</h3>
            <p class="text-gray-600">Задачи появятся здесь, когда их назначат на вас</p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php if (!empty($pendingTasks)): ?>
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-clock text-yellow-500 mr-2"></i>
                            Ожидающие задачи (<?php echo count($pendingTasks); ?>)
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($pendingTasks as $task): ?>
                            <?php include 'task-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($inProgressTasks)): ?>
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-spinner text-blue-500 mr-2"></i>
                            В работе (<?php echo count($inProgressTasks); ?>)
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($inProgressTasks as $task): ?>
                            <?php include 'task-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($completedTasks)): ?>
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Выполненные (<?php echo count($completedTasks); ?>)
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($completedTasks as $task): ?>
                            <?php include 'task-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
