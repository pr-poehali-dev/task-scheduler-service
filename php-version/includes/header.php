<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'TaskFlow - Управление задачами'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-700: #374151;
            --gray-900: #111827;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .priority-urgent { color: var(--danger); }
        .priority-high { color: #f97316; }
        .priority-medium { color: var(--warning); }
        .priority-low { color: var(--success); }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-in-progress { background-color: #dbeafe; color: #1e40af; }
        .badge-completed { background-color: #d1fae5; color: #065f46; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .sidebar {
            width: 250px;
            background: var(--gray-900);
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            background: var(--gray-50);
            min-height: 100vh;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }
        
        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 1000;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-tasks"></i> TaskFlow
            </h1>
        </div>
        
        <nav class="py-4">
            <a href="/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Главная</span>
            </a>
            
            <a href="/tasks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Все задачи</span>
            </a>
            
            <a href="/my-tasks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-tasks.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                <span>Мои задачи</span>
            </a>
            
            <?php if ($_SESSION['user_role'] !== 'user'): ?>
            <a href="/team.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Команда</span>
            </a>
            <?php endif; ?>
            
            <a href="/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Профиль</span>
            </a>
            
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase">
                Администрирование
            </div>
            
            <a href="/admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i>
                <span>Панель админа</span>
            </a>
            
            <a href="/users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i>
                <span>Пользователи</span>
            </a>
            
            <a href="/settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Настройки</span>
            </a>
            <?php endif; ?>
            
            <a href="/logout.php" class="nav-link mt-auto">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выход</span>
            </a>
        </nav>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-white">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </div>
                    <div class="text-xs text-gray-400">
                        <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'user'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="mb-6">
            <button class="md:hidden btn-primary" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
<?php endif; ?>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('mobile-open');
}
</script>