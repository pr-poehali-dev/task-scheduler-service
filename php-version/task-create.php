<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Создать задачу - TaskFlow';
include __DIR__ . '/includes/header.php';

$db = getDB();

// Получаем список пользователей для назначения
$users = $db->query("
    SELECT id, full_name, email, role
    FROM users
    WHERE is_active = 1
    ORDER BY full_name
")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-blue-500"></i>
            Создать новую задачу
        </h2>
        <p class="text-gray-600 mt-2">Заполните форму для создания задачи</p>
    </div>
    
    <div class="bg-white rounded-lg shadow-lg p-8">
        <form id="createTaskForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Название задачи <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    required
                    maxlength="500"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Введите название задачи"
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
                    placeholder="Подробное описание задачи (необязательно)"
                ></textarea>
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
                        <option value="medium">🟡 Средний</option>
                        <option value="low">🟢 Низкий</option>
                        <option value="high">🟠 Высокий</option>
                        <option value="urgent">🔴 Срочный</option>
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
                        <option value="pending">⏳ Ожидает</option>
                        <option value="in_progress">🔄 В работе</option>
                        <option value="completed">✅ Выполнено</option>
                        <option value="cancelled">❌ Отменено</option>
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
                        min="<?php echo date('Y-m-d'); ?>"
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
                            <option value="<?php echo $user['id']; ?>">
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
                    <i class="fas fa-check mr-2"></i>
                    Создать задачу
                </button>
                
                <a 
                    href="/index.php" 
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
document.getElementById('createTaskForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const button = document.getElementById('submitButton');
    const errorDiv = document.getElementById('errorMessage');
    const successDiv = document.getElementById('successMessage');
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Создание...';
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');
    
    const token = localStorage.getItem('auth_token');
    
    if (!token) {
        errorDiv.textContent = 'Ошибка авторизации. Пожалуйста, войдите снова.';
        errorDiv.classList.remove('hidden');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Создать задачу';
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
        const response = await fetch('/api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            successDiv.textContent = 'Задача успешно создана! Перенаправление...';
            successDiv.classList.remove('hidden');
            
            setTimeout(() => {
                window.location.href = '/task.php?id=' + data.task.id;
            }, 1500);
        } else {
            throw new Error(data.error || 'Ошибка создания задачи');
        }
    } catch (error) {
        errorDiv.textContent = error.message;
        errorDiv.classList.remove('hidden');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Создать задачу';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
