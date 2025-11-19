<?php
$priorityColors = [
    'urgent' => 'text-red-500',
    'high' => 'text-orange-500',
    'medium' => 'text-yellow-500',
    'low' => 'text-green-500'
];
$priorityColor = $priorityColors[$task['priority']] ?? 'text-gray-500';
$isOverdue = $task['due_date'] && strtotime($task['due_date']) < strtotime('today') && $task['status'] !== 'completed';
?>

<div class="p-6 hover:bg-gray-50 transition">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-circle <?php echo $priorityColor; ?> text-xs"></i>
                <h4 class="text-lg font-medium text-gray-800">
                    <a href="/task.php?id=<?php echo $task['id']; ?>" class="hover:text-blue-600">
                        <?php echo htmlspecialchars($task['title']); ?>
                    </a>
                </h4>
            </div>
            
            <?php if ($task['description']): ?>
                <p class="text-gray-600 text-sm mb-3">
                    <?php 
                    $desc = htmlspecialchars($task['description']);
                    echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                    ?>
                </p>
            <?php endif; ?>
            
            <div class="flex items-center gap-4 text-sm text-gray-500">
                <?php if (isset($task['creator_name'])): ?>
                    <span>
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($task['creator_name']); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($task['due_date']): ?>
                    <span class="<?php echo $isOverdue ? 'text-red-500 font-semibold' : ''; ?>">
                        <i class="fas fa-calendar"></i>
                        <?php 
                        $dueDate = new DateTime($task['due_date']);
                        echo $dueDate->format('d.m.Y');
                        if ($isOverdue) echo ' (просрочено)';
                        ?>
                    </span>
                <?php endif; ?>
                
                <span>
                    <i class="fas fa-clock"></i>
                    <?php 
                    $createdAt = new DateTime($task['created_at']);
                    echo $createdAt->format('d.m.Y');
                    ?>
                </span>
            </div>
        </div>
        
        <a href="/task.php?id=<?php echo $task['id']; ?>" class="text-blue-500 hover:text-blue-600 ml-4">
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
