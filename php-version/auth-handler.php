<?php
session_start();
require_once __DIR__ . '/config.php';

// Эта страница обрабатывает авторизацию через токен из localStorage

if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// Проверяем наличие токена в URL (передаётся из JavaScript)
$token = $_GET['token'] ?? null;

if (!$token) {
    // Если токена нет, показываем страницу с JavaScript
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Авторизация - TaskFlow</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
            <p class="text-gray-600">Авторизация...</p>
        </div>
        
        <script>
            // Получаем токен из localStorage
            const token = localStorage.getItem('auth_token');
            
            if (token) {
                // Перенаправляем с токеном в URL
                window.location.href = '/auth-handler.php?token=' + encodeURIComponent(token);
            } else {
                // Если токена нет, отправляем на логин
                window.location.href = '/login.php';
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Проверяем токен в базе данных
$db = getDB();

$stmt = $db->prepare("
    SELECT u.id, u.email, u.full_name, u.role
    FROM sessions s
    JOIN users u ON s.user_id = u.id
    WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    // Создаём PHP-сессию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['auth_token'] = $token;
    
    // Перенаправляем на главную
    header('Location: /index.php');
    exit;
} else {
    // Токен недействителен
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ошибка авторизации - TaskFlow</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-md text-center">
            <div class="text-red-500 text-5xl mb-4">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Ошибка авторизации</h1>
            <p class="text-gray-600 mb-6">Ваш токен недействителен или истёк</p>
            <a href="/login.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg transition">
                Войти снова
            </a>
        </div>
        
        <script>
            // Очищаем localStorage
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('user_name');
            localStorage.removeItem('user_role');
        </script>
    </body>
    </html>
    <?php
    exit;
}
