<?php
require_once __DIR__ . '/../config.php';

setCorsHeaders();

class AuthAPI {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function login() {
        $input = getJsonInput();
        
        if (!isset($input['email']) || !isset($input['password'])) {
            jsonResponse(['error' => 'Email и пароль обязательны'], 400);
        }
        
        $email = trim($input['email']);
        $password = $input['password'];
        
        if (!isValidEmail($email)) {
            jsonResponse(['error' => 'Неверный формат email'], 400);
        }
        
        $stmt = $this->db->prepare("
            SELECT id, email, password_hash, full_name, role, is_active 
            FROM users 
            WHERE email = ? AND is_active = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Неверный email или пароль'], 401);
        }
        
        $token = $this->createSession($user['id']);
        
        jsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ]);
    }
    
    public function register() {
        $input = getJsonInput();
        
        $required = ['email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty(trim($input[$field]))) {
                jsonResponse(['error' => "Поле {$field} обязательно"], 400);
            }
        }
        
        $email = trim($input['email']);
        $password = $input['password'];
        $full_name = trim($input['full_name']);
        $role = isset($input['role']) ? $input['role'] : 'user';
        
        if (!isValidEmail($email)) {
            jsonResponse(['error' => 'Неверный формат email'], 400);
        }
        
        if (strlen($password) < 6) {
            jsonResponse(['error' => 'Пароль должен быть не менее 6 символов'], 400);
        }
        
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Пользователь с таким email уже существует'], 409);
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, full_name, role) 
            VALUES (?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([$email, $password_hash, $full_name, $role]);
            $userId = $this->db->lastInsertId();
            
            $token = $this->createSession($userId);
            
            jsonResponse([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'email' => $email,
                    'full_name' => $full_name,
                    'role' => $role
                ]
            ], 201);
        } catch (Exception $e) {
            logError('Registration error', ['error' => $e->getMessage()]);
            jsonResponse(['error' => 'Ошибка регистрации'], 500);
        }
    }
    
    public function logout() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            jsonResponse(['error' => 'Токен не предоставлен'], 401);
        }
        
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE token = ?");
        $stmt->execute([$token]);
        
        jsonResponse(['success' => true, 'message' => 'Успешный выход']);
    }
    
    public function verifyToken() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            jsonResponse(['error' => 'Токен не предоставлен'], 401);
        }
        
        $user = $this->getUserByToken($token);
        
        if (!$user) {
            jsonResponse(['error' => 'Неверный или истекший токен'], 401);
        }
        
        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ]);
    }
    
    private function createSession($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->db->prepare("
            INSERT INTO sessions (user_id, token, expires_at, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $expiresAt, $ipAddress, $userAgent]);
        
        return $token;
    }
    
    private function getBearerToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    public function getUserByToken($token) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.email, u.full_name, u.role, u.is_active
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
}

$auth = new AuthAPI();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $path = $_GET['action'] ?? '';
        
        switch ($path) {
            case 'login':
                $auth->login();
                break;
            case 'register':
                $auth->register();
                break;
            case 'logout':
                $auth->logout();
                break;
            case 'verify':
                $auth->verifyToken();
                break;
            default:
                jsonResponse(['error' => 'Неизвестное действие'], 404);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Метод не поддерживается'], 405);
}
