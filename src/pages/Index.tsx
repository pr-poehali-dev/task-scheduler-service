import { useState, useEffect } from 'react';
import Sidebar from '@/components/Sidebar';
import DashboardTab from '@/components/DashboardTab';
import TasksTab from '@/components/TasksTab';
import TabsContent from '@/components/TabsContent';
import AuthScreen from '@/components/AuthScreen';

interface Task {
  id: number;
  title: string;
  completed: boolean;
  assignedTo?: string;
  priority: 'low' | 'medium' | 'high';
}

interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  tasksCompleted: number;
  password: string;
}

const Index = () => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [activeTab, setActiveTab] = useState('dashboard');
  
  const [registeredUsers, setRegisteredUsers] = useState<User[]>([
    { id: 1, name: 'Алексей Иванов', email: 'alex@company.ru', role: 'admin', tasksCompleted: 24, password: 'admin123' },
    { id: 2, name: 'Мария Петрова', email: 'maria@company.ru', role: 'user', tasksCompleted: 18, password: 'user123' },
    { id: 3, name: 'Иван Сидоров', email: 'ivan@company.ru', role: 'user', tasksCompleted: 31, password: 'user123' },
    { id: 4, name: 'Елена Смирнова', email: 'elena@company.ru', role: 'user', tasksCompleted: 15, password: 'user123' },
    { id: 5, name: 'Дмитрий Козлов', email: 'dmitry@company.ru', role: 'user', tasksCompleted: 22, password: 'user123' },
  ]);

  const [tasks, setTasks] = useState<Task[]>([
    { id: 1, title: 'Подготовить презентацию для клиента', completed: true, assignedTo: 'Алексей Иванов', priority: 'high' },
    { id: 2, title: 'Провести код-ревью PR #234', completed: false, assignedTo: 'Алексей Иванов', priority: 'medium' },
    { id: 3, title: 'Обновить документацию API', completed: false, assignedTo: 'Мария Петрова', priority: 'low' },
    { id: 4, title: 'Исправить баг в модуле авторизации', completed: true, assignedTo: 'Иван Сидоров', priority: 'high' },
    { id: 5, title: 'Планирование спринта на следующую неделю', completed: false, assignedTo: 'Алексей Иванов', priority: 'medium' },
  ]);

  const [notes, setNotes] = useState([
    { id: 1, text: 'Не забыть купить подарок коллеге', completed: false },
    { id: 2, text: 'Посмотреть вебинар по React 19', completed: true },
    { id: 3, text: 'Записаться к стоматологу', completed: false },
  ]);

  const [newTask, setNewTask] = useState('');
  const [newNote, setNewNote] = useState('');
  const [selectedUser, setSelectedUser] = useState<string>('');

  useEffect(() => {
    const savedAuth = localStorage.getItem('taskflow_auth');
    if (savedAuth) {
      const user = JSON.parse(savedAuth);
      setCurrentUser(user);
      setIsAuthenticated(true);
    }
  }, []);

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

  const toggleTask = (id: number) => {
    setTasks(tasks.map(t => t.id === id ? { ...t, completed: !t.completed } : t));
  };

  const toggleNote = (id: number) => {
    setNotes(notes.map(n => n.id === id ? { ...n, completed: !n.completed } : n));
  };

  const addTask = () => {
    if (newTask.trim()) {
      const task: Task = {
        id: tasks.length + 1,
        title: newTask,
        completed: false,
        assignedTo: selectedUser || currentUser?.name,
        priority: 'medium'
      };
      setTasks([...tasks, task]);
      setNewTask('');
      setSelectedUser('');
    }
  };

  const addNote = () => {
    if (newNote.trim()) {
      setNotes([...notes, { id: notes.length + 1, text: newNote, completed: false }]);
      setNewNote('');
    }
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
            />
          )}

          {activeTab === 'tasks' && (
            <TasksTab
              tasks={tasks}
              newTask={newTask}
              setNewTask={setNewTask}
              addTask={addTask}
              toggleTask={toggleTask}
            />
          )}

          <TabsContent
            activeTab={activeTab}
            currentUser={currentUser}
            users={registeredUsers}
            tasks={tasks}
            notes={notes}
            newNote={newNote}
            setNewNote={setNewNote}
            addNote={addNote}
            toggleNote={toggleNote}
            newTask={newTask}
            setNewTask={setNewTask}
            selectedUser={selectedUser}
            setSelectedUser={setSelectedUser}
            addTask={addTask}
            onLogout={handleLogout}
          />
        </main>
      </div>
    </div>
  );
};

export default Index;
