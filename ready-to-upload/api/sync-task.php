<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';

try {
    $db = getDB();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT id, status FROM tasks WHERE is_deleted = 0 ORDER BY id");
        $tasks = $stmt->fetchAll();
        
        $result = [];
        foreach ($tasks as $task) {
            $result[(string)$task['id']] = ($task['status'] === 'completed');
        }
        
        echo json_encode($result);
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['taskId'] ?? null;
        $completed = $input['completed'] ?? true;
        
        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['error' => 'taskId is required']);
            exit();
        }
        
        $status = $completed ? 'completed' : 'pending';
        
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $taskId]);
        
        $stmt = $db->prepare("SELECT id, title, status FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if ($task) {
            echo json_encode([
                'success' => true,
                'task' => [
                    'id' => $task['id'],
                    'title' => $task['title'],
                    'completed' => ($task['status'] === 'completed')
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
        }
        exit();
    }
    
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
