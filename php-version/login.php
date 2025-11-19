<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$pageTitle = 'Вход - TaskFlow';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800">
                    <i class="fas fa-tasks text-blue-500"></i>
                    TaskFlow
                </h1>
                <p class="text-gray-600 mt-2">Войдите в свой аккаунт</p>
            </div>
            
            <form id="loginForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="user@example.com"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Пароль
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                </div>
                
                <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                </div>
                
                <button 
                    type="submit" 
                    id="loginButton"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg transition duration-200"
                >
                    Войти
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Нет аккаунта? 
                    <a href="/register.php" class="text-blue-500 hover:text-blue-600 font-medium">
                        Зарегистрироваться
                    </a>
                </p>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Тестовые аккаунты:</h3>
                <div class="space-y-2 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span>👑 Админ:</span>
                        <code class="bg-gray-100 px-2 py-1 rounded">admin@taskflow.com / admin123</code>
                    </div>
                    <div class="flex justify-between">
                        <span>👤 Менеджер:</span>
                        <code class="bg-gray-100 px-2 py-1 rounded">manager@taskflow.com / manager123</code>
                    </div>
                    <div class="flex justify-between">
                        <span>👥 Пользователь:</span>
                        <code class="bg-gray-100 px-2 py-1 rounded">user@taskflow.com / user123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const button = document.getElementById('loginButton');
            const errorDiv = document.getElementById('errorMessage');
            
            button.disabled = true;
            button.textContent = 'Вход...';
            errorDiv.classList.add('hidden');
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            try {
                const response = await fetch('/api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    localStorage.setItem('auth_token', data.token);
                    localStorage.setItem('user_id', data.user.id);
                    localStorage.setItem('user_name', data.user.full_name);
                    localStorage.setItem('user_role', data.user.role);
                    
                    window.location.href = '/auth-handler.php';
                } else {
                    throw new Error(data.error || 'Ошибка входа');
                }
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.classList.remove('hidden');
                button.disabled = false;
                button.textContent = 'Войти';
            }
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('registered') === 'true') {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg';
            notification.textContent = 'Регистрация успешна! Теперь войдите.';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    </script>
</body>
</html>