import { useState, useEffect } from 'react';
import Sidebar from '@/components/Sidebar';
import DashboardTab from '@/components/DashboardTab';
import TasksTab from '@/components/TasksTab';
import TabsContent from '@/components/TabsContent';
import AuthScreen from '@/components/AuthScreen';
import { BACKEND_URLS } from '@/config/backend';

interface Task {
  id: number;
  title: string;
  completed: boolean;
  assignedTo: string[];
  priority: 'low' | 'medium' | 'high';
  urgent?: boolean;
  deadline?: string;
  createdBy?: string;
  createdAt?: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  tasksCompleted: number;
  password: string;
  avatar?: string;
  telegramChatId?: number;
}

const Index = () => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dismissedNotifications, setDismissedNotifications] = useState<number[]>([]);
  
  const [registeredUsers, setRegisteredUsers] = useState<User[]>([
    { id: 1, name: 'Алексей Иванов', email: 'alex@company.ru', role: 'admin', tasksCompleted: 24, password: 'admin123' },
    { id: 2, name: 'Мария Петрова', email: 'maria@company.ru', role: 'user', tasksCompleted: 18, password: 'user123' },
    { id: 3, name: 'Иван Сидоров', email: 'ivan@company.ru', role: 'user', tasksCompleted: 31, password: 'user123' },
    { id: 4, name: 'Елена Смирнова', email: 'elena@company.ru', role: 'user', tasksCompleted: 15, password: 'user123' },
    { id: 5, name: 'Дмитрий Козлов', email: 'dmitry@company.ru', role: 'user', tasksCompleted: 22, password: 'user123' },
  ]);

  const [tasks, setTasks] = useState<Task[]>([
    { 
      id: 1, 
      title: 'Подготовить презентацию для клиента', 
      completed: true, 
      assignedTo: ['Алексей Иванов'], 
      priority: 'high',
      deadline: '2025-01-20',
      createdBy: 'Алексей Иванов',
      urgent: false
    },
    { 
      id: 2, 
      title: 'Провести код-ревью PR #234', 
      completed: false, 
      assignedTo: ['Алексей Иванов'], 
      priority: 'medium',
      deadline: '2025-01-18',
      createdBy: 'Алексей Иванов',
      urgent: true
    },
    { 
      id: 3, 
      title: 'Обновить документацию API', 
      completed: false, 
      assignedTo: ['Мария Петрова', 'Иван Сидоров'], 
      priority: 'low',
      deadline: '2025-01-25',
      createdBy: 'Алексей Иванов',
      urgent: false
    },
  ]);

  const [notes, setNotes] = useState([
    { id: 1, text: 'Не забыть купить подарок коллеге', completed: false },
    { id: 2, text: 'Посмотреть вебинар по React 19', completed: true },
    { id: 3, text: 'Записаться к стоматологу', completed: false },
  ]);

  useEffect(() => {
    const savedAuth = localStorage.getItem('taskflow_auth');
    if (savedAuth) {
      const user = JSON.parse(savedAuth);
      setCurrentUser(user);
      setIsAuthenticated(true);
    }
  }, []);

  useEffect(() => {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  }, []);

  const sendNotification = (title: string, body: string) => {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(title, {
        body,
        icon: '/favicon.ico',
        badge: '/favicon.ico'
      });
    }
  };

  const handleLogin = (user: User) => {
    setCurrentUser(user);
    setIsAuthenticated(true);
    localStorage.setItem('taskflow_auth', JSON.stringify(user));
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setIsAuthenticated(false);
    localStorage.removeItem('taskflow_auth');
    setActiveTab('dashboard');
  };

  const handleRegister = (user: User) => {
    setRegisteredUsers([...registeredUsers, user]);
  };

  const toggleTask = async (id: number) => {
    const task = tasks.find(t => t.id === id);
    if (!task) return;

    const newCompletedStatus = !task.completed;
    setTasks(tasks.map(t => t.id === id ? { ...t, completed: newCompletedStatus } : t));

    try {
      await fetch(BACKEND_URLS.SYNC_TASK, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          taskId: id,
          completed: newCompletedStatus
        })
      });
    } catch (error) {
      console.error('Error syncing task status:', error);
    }
  };

  const deleteTask = (id: number) => {
    setTasks(tasks.filter(t => t.id !== id));
  };

  const toggleNote = (id: number) => {
    setNotes(notes.map(n => n.id === id ? { ...n, completed: !n.completed } : n));
  };

  const deleteNote = (id: number) => {
    setNotes(notes.filter(n => n.id !== id));
  };

  const addTask = async (task: Omit<Task, 'id'>) => {
    const newTask: Task = {
      id: tasks.length + 1,
      ...task,
      createdBy: currentUser?.name,
      createdAt: new Date().toISOString()
    };
    setTasks([newTask, ...tasks]);
    
    if (task.assignedTo.length > 0) {
      task.assignedTo.forEach(assignee => {
        if (assignee !== currentUser?.name) {
          sendNotification(
            '🎯 Новая задача назначена!',
            `Задача "${task.title}" назначена ${task.urgent ? '(СРОЧНО!)' : ''}`
          );
        }
      });

      try {
        const response = await fetch(BACKEND_URLS.NOTIFY_TASK, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            taskId: newTask.id,
            title: task.title,
            deadline: task.deadline || 'Не указан',
            urgent: task.urgent || false,
            createdBy: currentUser?.name || 'Администратор',
            assignedTo: task.assignedTo
          })
        });

        if (!response.ok) {
          console.error('Failed to send Telegram notification');
        }
      } catch (error) {
        console.error('Error sending Telegram notification:', error);
      }
    }
  };

  const addNote = (text: string) => {
    if (text.trim()) {
      setNotes([...notes, { id: notes.length + 1, text, completed: false }]);
    }
  };

  const dismissNotification = (taskId: number) => {
    setDismissedNotifications([...dismissedNotifications, taskId]);
  };

  const updateUser = (updatedUser: User) => {
    setCurrentUser(updatedUser);
    localStorage.setItem('taskflow_auth', JSON.stringify(updatedUser));
    setRegisteredUsers(registeredUsers.map(u => u.id === updatedUser.id ? updatedUser : u));
  };

  if (!isAuthenticated || !currentUser) {
    return (
      <AuthScreen
        onLogin={handleLogin}
        registeredUsers={registeredUsers}
        onRegister={handleRegister}
      />
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-background via-background to-muted/20">
      <div className="flex">
        <Sidebar currentUser={currentUser} activeTab={activeTab} setActiveTab={setActiveTab} />

        <main className="flex-1 p-8">
          {activeTab === 'dashboard' && (
            <DashboardTab
              currentUser={currentUser}
              tasks={tasks}
              notes={notes}
              toggleTask={toggleTask}
              dismissedNotifications={dismissedNotifications}
              dismissNotification={dismissNotification}
            />
          )}

          {activeTab === 'tasks' && (
            <TasksTab
              currentUser={currentUser}
              tasks={tasks}
              users={registeredUsers}
              toggleTask={toggleTask}
              deleteTask={deleteTask}
            />
          )}

          <TabsContent
            activeTab={activeTab}
            currentUser={currentUser}
            users={registeredUsers}
            tasks={tasks}
            notes={notes}
            addNote={addNote}
            toggleNote={toggleNote}
            deleteNote={deleteNote}
            addTask={addTask}
            deleteTask={deleteTask}
            onLogout={handleLogout}
            onUpdateUser={updateUser}
          />
        </main>
      </div>
    </div>
  );
};

export default Index;