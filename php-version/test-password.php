<?php
// Тестовая страница для проверки паролей
require_once __DIR__ . '/config.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h1>Тест паролей</h1>";
echo "<p>Пароль: <strong>{$password}</strong></p>";
echo "<p>Новый хеш: <code>{$hash}</code></p>";

$db = getDB();

// Обновляем пароли для всех тестовых пользователей
$users = [
    ['email' => 'admin@taskflow.com', 'name' => 'Администратор'],
    ['email' => 'manager@taskflow.com', 'name' => 'Менеджер'],
    ['email' => 'user@taskflow.com', 'name' => 'Иван Иванов']
];

echo "<h2>Обновление паролей:</h2>";

foreach ($users as $user) {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$hash, $user['email']]);
    
    echo "<p>✅ Пароль обновлён для: <strong>{$user['name']}</strong> ({$user['email']})</p>";
}

echo "<hr>";
echo "<h2>Проверка паролей:</h2>";

$stmt = $db->query("SELECT id, email, full_name, password_hash FROM users LIMIT 3");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    $isValid = password_verify($password, $user['password_hash']);
    $status = $isValid ? '✅ Верно' : '❌ Неверно';
    
    echo "<p>{$status} - {$user['full_name']} ({$user['email']})</p>";
}

echo "<hr>";
echo "<p><strong>Теперь можете войти с паролем: admin123</strong></p>";
echo "<p><a href='/login.php'>Перейти к входу →</a></p>";
