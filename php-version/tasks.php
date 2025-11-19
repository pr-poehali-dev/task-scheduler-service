<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Все задачи - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$statusFilter = $_GET['status'] ?? null;
$priorityFilter = $_GET['priority'] ?? null;
$assignedFilter = $_GET['assigned'] ?? null;

$sql = "
    SELECT 
        t.*,
        creator.full_name AS creator_name,
        creator.email AS creator_email,
        assignee.full_name AS assignee_name,
        assignee.email AS assignee_email
    FROM tasks t
    JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE t.is_deleted = 0
";

$params = [];

if ($statusFilter) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}

if ($assignedFilter === 'me') {
    $sql .= " AND t.assigned_to = ?";
    $params[] = $userId;
}

if ($userRole === 'user') {
    $sql .= " AND (t.created_by = ? OR t.assigned_to = ?)";
    $params[] = $userId;
    $params[] = $userId;
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

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Все задачи</h2>
            <p class="text-gray-600 mt-2">Всего задач: <?php echo count($tasks); ?></p>
        </div>
        <a href="/task-create.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition">
            <i class="fas fa-plus mr-2"></i>
            Создать задачу
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Фильтры</h3>
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Все</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Выполнено</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Приоритет</label>
                    <select id="priorityFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Все</option>
                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Срочно</option>
                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>Высокий</option>
                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Средний</option>
                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Низкий</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Назначено</label>
                    <select id="assignedFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Все</option>
                        <option value="me" <?php echo $assignedFilter === 'me' ? 'selected' : ''; ?>>Мне</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button onclick="applyFilters()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                        Применить
                    </button>
                    <button onclick="clearFilters()" class="ml-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-medium">
                        Сбросить
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($tasks)): ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Задач не найдено</h3>
            <p class="text-gray-600 mb-6">Попробуйте изменить фильтры или создайте новую задачу</p>
            <a href="/task-create.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-plus mr-2"></i>
                Создать задачу
            </a>
        </div>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($tasks as $task): ?>
                <?php
                $priorityColors = [
                    'urgent' => 'text-red-500 bg-red-50',
                    'high' => 'text-orange-500 bg-orange-50',
                    'medium' => 'text-yellow-500 bg-yellow-50',
                    'low' => 'text-green-500 bg-green-50'
                ];
                $priorityClass = $priorityColors[$task['priority']] ?? 'text-gray-500 bg-gray-50';
                
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
                
                $isOverdue = $task['due_date'] && strtotime($task['due_date']) < strtotime('today') && $task['status'] !== 'completed';
                ?>
                
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6 <?php echo $isOverdue ? 'border-l-4 border-red-500' : ''; ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $priorityClass; ?>">
                                    <i class="fas fa-circle text-xs"></i>
                                </div>
                                
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-800">
                                        <a href="/task.php?id=<?php echo $task['id']; ?>" class="hover:text-blue-600">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($task['description']): ?>
                                        <p class="text-gray-600 text-sm mt-1">
                                            <?php 
                                            $desc = htmlspecialchars($task['description']);
                                            echo strlen($desc) > 150 ? substr($desc, 0, 150) . '...' : $desc;
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                                    <?php echo $statusName; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-6 text-sm text-gray-500 ml-13">
                                <?php if ($task['assignee_name']): ?>
                                    <span>
                                        <i class="fas fa-user mr-1"></i>
                                        <?php echo htmlspecialchars($task['assignee_name']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($task['due_date']): ?>
                                    <span class="<?php echo $isOverdue ? 'text-red-500 font-semibold' : ''; ?>">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php 
                                        $dueDate = new DateTime($task['due_date']);
                                        echo $dueDate->format('d.m.Y');
                                        if ($isOverdue) echo ' (просрочено)';
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span>
                                    <i class="fas fa-user-circle mr-1"></i>
                                    <?php echo htmlspecialchars($task['creator_name']); ?>
                                </span>
                                
                                <span>
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php 
                                    $createdAt = new DateTime($task['created_at']);
                                    echo $createdAt->format('d.m.Y H:i');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    const assigned = document.getElementById('assignedFilter').value;
    
    let url = '/tasks.php?';
    if (status) url += 'status=' + status + '&';
    if (priority) url += 'priority=' + priority + '&';
    if (assigned) url += 'assigned=' + assigned + '&';
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = '/tasks.php';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
