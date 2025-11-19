<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$pageTitle = 'Регистрация - TaskFlow';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <p class="text-gray-600 mt-2">Создайте новый аккаунт</p>
            </div>
            
            <form id="registerForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Полное имя
                    </label>
                    <input 
                        type="text" 
                        id="full_name" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Иван Иванов"
                    >
                </div>
                
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
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Минимум 6 символов"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        Минимум 6 символов
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Подтвердите пароль
                    </label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        required
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Повторите пароль"
                    >
                </div>
                
                <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                </div>
                
                <button 
                    type="submit" 
                    id="registerButton"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg transition duration-200"
                >
                    Зарегистрироваться
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Уже есть аккаунт? 
                    <a href="/login.php" class="text-blue-500 hover:text-blue-600 font-medium">
                        Войти
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const button = document.getElementById('registerButton');
            const errorDiv = document.getElementById('errorMessage');
            
            button.disabled = true;
            button.textContent = 'Регистрация...';
            errorDiv.classList.add('hidden');
            
            const full_name = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const password_confirm = document.getElementById('password_confirm').value;
            
            if (password !== password_confirm) {
                errorDiv.textContent = 'Пароли не совпадают';
                errorDiv.classList.remove('hidden');
                button.disabled = false;
                button.textContent = 'Зарегистрироваться';
                return;
            }
            
            if (password.length < 6) {
                errorDiv.textContent = 'Пароль должен быть не менее 6 символов';
                errorDiv.classList.remove('hidden');
                button.disabled = false;
                button.textContent = 'Зарегистрироваться';
                return;
            }
            
            try {
                const response = await fetch('/api/auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        email, 
                        password, 
                        full_name 
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    localStorage.setItem('auth_token', data.token);
                    localStorage.setItem('user_id', data.user.id);
                    localStorage.setItem('user_name', data.user.full_name);
                    localStorage.setItem('user_role', data.user.role);
                    
                    window.location.href = '/auth-handler.php';
                } else {
                    throw new Error(data.error || 'Ошибка регистрации');
                }
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.classList.remove('hidden');
                button.disabled = false;
                button.textContent = 'Зарегистрироваться';
            }
        });
    </script>
</body>
</html>