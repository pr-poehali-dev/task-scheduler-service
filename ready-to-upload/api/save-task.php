<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$title = $input['title'] ?? null;
$priority = $input['priority'] ?? 'medium';
$urgent = $input['urgent'] ?? false;
$deadline = $input['deadline'] ?? null;
$createdBy = $input['createdBy'] ?? null;
$assignedTo = $input['assignedTo'] ?? [];

if (!$title) {
    http_response_code(400);
    echo json_encode(['error' => 'Title is required']);
    exit();
}

try {
    $db = getDB();
    
    $creatorId = null;
    if ($createdBy) {
        $stmt = $db->prepare("SELECT id FROM users WHERE full_name = ?");
        $stmt->execute([$createdBy]);
        $creator = $stmt->fetch();
        if ($creator) {
            $creatorId = $creator['id'];
        }
    }
    
    $status = 'pending';
    
    $stmt = $db->prepare(
        "INSERT INTO tasks (title, description, priority, status, deadline, created_by, is_deleted) " .
        "VALUES (?, '', ?, ?, ?, ?, 0)"
    );
    $stmt->execute([$title, $priority, $status, $deadline, $creatorId]);
    $taskId = $db->lastInsertId();
    
    foreach ($assignedTo as $userName) {
        $stmt = $db->prepare("SELECT id FROM users WHERE full_name = ?");
        $stmt->execute([$userName]);
        $user = $stmt->fetch();
        
        if ($user) {
            $stmt = $db->prepare("UPDATE tasks SET assigned_to = ? WHERE id = ?");
            $stmt->execute([$user['id'], $taskId]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'taskId' => $taskId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
