<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$taskId = $_GET['id'] ?? null;

if (!$taskId) {
    header('Location: /tasks.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$stmt = $db->prepare("
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
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: /tasks.php');
    exit;
}

if ($userRole === 'user') {
    if ($task['created_by'] != $userId && $task['assigned_to'] != $userId) {
        header('Location: /tasks.php');
        exit;
    }
}

$stmt = $db->prepare("
    SELECT c.*, u.full_name, u.email
    FROM task_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.task_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$taskId]);
$comments = $stmt->fetchAll();

$pageTitle = htmlspecialchars($task['title']) . ' - TaskFlow';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="/tasks.php" class="text-blue-500 hover:text-blue-600">
            <i class="fas fa-arrow-left mr-2"></i>
            Назад к задачам
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow p-8">
        <div class="flex justify-between items-start mb-6">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($task['title']); ?>
                </h1>
                
                <div class="flex items-center gap-4">
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
                    <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $badgeClass; ?>">
                        <?php echo $statusName; ?>
                    </span>
                    
                    <?php
                    $priorityBadges = [
                        'urgent' => 'bg-red-100 text-red-800',
                        'high' => 'bg-orange-100 text-orange-800',
                        'medium' => 'bg-yellow-100 text-yellow-800',
                        'low' => 'bg-green-100 text-green-800'
                    ];
                    $priorityNames = [
                        'urgent' => 'Срочно',
                        'high' => 'Высокий',
                        'medium' => 'Средний',
                        'low' => 'Низкий'
                    ];
                    $priorityClass = $priorityBadges[$task['priority']] ?? 'bg-gray-100 text-gray-800';
                    $priorityName = $priorityNames[$task['priority']] ?? $task['priority'];
                    ?>
                    <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $priorityClass; ?>">
                        <?php echo $priorityName; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($task['created_by'] == $userId || $userRole !== 'user'): ?>
                <div class="flex gap-2">
                    <a href="/task-edit.php?id=<?php echo $task['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-edit mr-2"></i>
                        Редактировать
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Создана</h3>
                <p class="text-gray-600">
                    <?php 
                    $createdAt = new DateTime($task['created_at']);
                    echo $createdAt->format('d.m.Y H:i');
                    ?>
                    <br>
                    <span class="text-sm">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($task['creator_name']); ?>
                    </span>
                </p>
            </div>
            
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Назначена</h3>
                <p class="text-gray-600">
                    <?php if ($task['assignee_name']): ?>
                        <i class="fas fa-user-check mr-1"></i>
                        <?php echo htmlspecialchars($task['assignee_name']); ?>
                        <br>
                        <span class="text-sm"><?php echo htmlspecialchars($task['assignee_email']); ?></span>
                    <?php else: ?>
                        <span class="text-gray-400">Не назначена</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($task['due_date']): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Срок выполнения</h3>
                    <p class="text-gray-600">
                        <?php 
                        $dueDate = new DateTime($task['due_date']);
                        $isOverdue = strtotime($task['due_date']) < strtotime('today') && $task['status'] !== 'completed';
                        ?>
                        <i class="fas fa-calendar mr-1"></i>
                        <span class="<?php echo $isOverdue ? 'text-red-500 font-semibold' : ''; ?>">
                            <?php echo $dueDate->format('d.m.Y'); ?>
                            <?php if ($isOverdue): ?>
                                <br><span class="text-sm text-red-500">(просрочено)</span>
                            <?php endif; ?>
                        </span>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($task['completed_at']): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Завершена</h3>
                    <p class="text-gray-600">
                        <?php 
                        $completedAt = new DateTime($task['completed_at']);
                        echo $completedAt->format('d.m.Y H:i');
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($task['description']): ?>
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Описание</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($task['description']); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($task['assigned_to'] == $userId && $task['status'] !== 'completed'): ?>
            <div class="mb-8">
                <button onclick="completeTask(<?php echo $task['id']; ?>)" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-check-circle mr-2"></i>
                    Отметить как выполненную
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-lg shadow p-8 mt-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-6">Комментарии (<?php echo count($comments); ?>)</h3>
        
        <form id="commentForm" class="mb-8">
            <textarea 
                id="commentText" 
                rows="3" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Добавить комментарий..."
            ></textarea>
            <button type="submit" class="mt-3 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-paper-plane mr-2"></i>
                Отправить
            </button>
        </form>
        
        <div id="commentsList" class="space-y-4">
            <?php if (empty($comments)): ?>
                <p class="text-gray-500 text-center py-8">Комментариев пока нет</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="border-l-4 border-blue-500 bg-gray-50 p-4 rounded">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($comment['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($comment['full_name']); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php 
                                        $commentDate = new DateTime($comment['created_at']);
                                        echo $commentDate->format('d.m.Y H:i');
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-700 ml-11"><?php echo htmlspecialchars($comment['comment']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const taskId = <?php echo $task['id']; ?>;

document.getElementById('commentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const textarea = document.getElementById('commentText');
    const comment = textarea.value.trim();
    
    if (!comment) {
        showNotification('Комментарий не может быть пустым', 'error');
        return;
    }
    
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch(`/api/tasks.php/${taskId}/comment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ comment })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Комментарий добавлен', 'success');
            textarea.value = '';
            location.reload();
        } else {
            throw new Error(data.error || 'Ошибка добавления комментария');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
});

async function completeTask(taskId) {
    if (!confirm('Отметить задачу как выполненную?')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch(`/api/tasks.php/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ status: 'completed' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Задача завершена!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.error || 'Ошибка обновления задачи');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
