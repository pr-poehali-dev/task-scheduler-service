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

$pageTitle = 'Редактировать задачу - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();

// Получаем задачу
$stmt = $db->prepare("
    SELECT t.*, creator.full_name AS creator_name
    FROM tasks t
    JOIN users creator ON t.created_by = creator.id
    WHERE t.id = ? AND t.is_deleted = 0
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    echo "<div class='max-w-4xl mx-auto'>";
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>";
    echo "Задача не найдена или удалена.";
    echo "</div>";
    echo "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Проверка прав доступа
$canEdit = false;
if ($_SESSION['user_role'] === 'admin' || 
    $task['created_by'] == $_SESSION['user_id'] ||
    $task['assigned_to'] == $_SESSION['user_id']) {
    $canEdit = true;
}

if (!$canEdit) {
    echo "<div class='max-w-4xl mx-auto'>";
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>";
    echo "У вас нет прав для редактирования этой задачи.";
    echo "</div>";
    echo "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Получаем список пользователей
$users = $db->query("
    SELECT id, full_name, email, role
    FROM users
    WHERE is_active = 1
    ORDER BY full_name
")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-edit text-blue-500"></i>
                Редактировать задачу
            </h2>
            <p class="text-gray-600 mt-2">ID задачи: #<?php echo $taskId; ?></p>
        </div>
        <a href="/task.php?id=<?php echo $taskId; ?>" class="text-blue-500 hover:text-blue-600">
            <i class="fas fa-arrow-left mr-2"></i>Назад к задаче
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-lg p-8">
        <form id="editTaskForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Название задачи <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    required
                    maxlength="500"
                    value="<?php echo htmlspecialchars($task['title']); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Описание
                </label>
                <textarea 
                    id="description" 
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Приоритет <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="priority" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>🟢 Низкий</option>
                        <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>🟡 Средний</option>
                        <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>🟠 Высокий</option>
                        <option value="urgent" <?php echo $task['priority'] === 'urgent' ? 'selected' : ''; ?>>🔴 Срочный</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Статус <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="status" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Ожидает</option>
                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>🔄 В работе</option>
                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>✅ Выполнено</option>
                        <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Отменено</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Срок выполнения
                    </label>
                    <input 
                        type="date" 
                        id="due_date"
                        value="<?php echo $task['due_date'] ?? ''; ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Назначить на
                    </label>
                    <select 
                        id="assigned_to"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">-- Не назначено --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $task['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?> 
                                (<?php echo htmlspecialchars($user['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            </div>
            
            <div id="successMessage" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            </div>
            
            <div class="flex gap-4">
                <button 
                    type="submit" 
                    id="submitButton"
                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg transition duration-200"
                >
                    <i class="fas fa-save mr-2"></i>
                    Сохранить изменения
                </button>
                
                <a 
                    href="/task.php?id=<?php echo $taskId; ?>" 
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition duration-200 text-center"
                >
                    <i class="fas fa-times mr-2"></i>
                    Отмена
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editTaskForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const button = document.getElementById('submitButton');
    const errorDiv = document.getElementById('errorMessage');
    const successDiv = document.getElementById('successMessage');
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение...';
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');
    
    const token = localStorage.getItem('auth_token');
    
    if (!token) {
        errorDiv.textContent = 'Ошибка авторизации. Пожалуйста, войдите снова.';
        errorDiv.classList.remove('hidden');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-save mr-2"></i>Сохранить изменения';
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 2000);
        return;
    }
    
    const formData = {
        title: document.getElementById('title').value.trim(),
        description: document.getElementById('description').value.trim() || null,
        priority: document.getElementById('priority').value,
        status: document.getElementById('status').value,
        due_date: document.getElementById('due_date').value || null,
        assigned_to: document.getElementById('assigned_to').value || null
    };
    
    try {
        const response = await fetch('/api/tasks.php/<?php echo $taskId; ?>', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            successDiv.textContent = 'Задача успешно обновлена! Перенаправление...';
            successDiv.classList.remove('hidden');
            
            setTimeout(() => {
                window.location.href = '/task.php?id=<?php echo $taskId; ?>';
            }, 1500);
        } else {
            throw new Error(data.error || 'Ошибка обновления задачи');
        }
    } catch (error) {
        errorDiv.textContent = error.message;
        errorDiv.classList.remove('hidden');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-save mr-2"></i>Сохранить изменения';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
