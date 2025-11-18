import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import Icon from '@/components/ui/icon';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

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
}

const Index = () => {
  const [currentUser] = useState<User>({ id: 1, name: 'Алексей Иванов', email: 'alex@company.ru', role: 'admin', tasksCompleted: 24 });
  const [activeTab, setActiveTab] = useState('dashboard');
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

  const [users] = useState<User[]>([
    { id: 1, name: 'Алексей Иванов', email: 'alex@company.ru', role: 'admin', tasksCompleted: 24 },
    { id: 2, name: 'Мария Петрова', email: 'maria@company.ru', role: 'user', tasksCompleted: 18 },
    { id: 3, name: 'Иван Сидоров', email: 'ivan@company.ru', role: 'user', tasksCompleted: 31 },
    { id: 4, name: 'Елена Смирнова', email: 'elena@company.ru', role: 'user', tasksCompleted: 15 },
    { id: 5, name: 'Дмитрий Козлов', email: 'dmitry@company.ru', role: 'user', tasksCompleted: 22 },
  ]);

  const [newTask, setNewTask] = useState('');
  const [newNote, setNewNote] = useState('');
  const [selectedUser, setSelectedUser] = useState<string>('');

  const weeklyData = [
    { day: 'Пн', completed: 4, created: 6 },
    { day: 'Вт', completed: 7, created: 5 },
    { day: 'Ср', completed: 5, created: 8 },
    { day: 'Чт', completed: 9, created: 7 },
    { day: 'Пт', completed: 6, created: 4 },
    { day: 'Сб', completed: 2, created: 1 },
    { day: 'Вс', completed: 1, created: 0 },
  ];

  const priorityData = [
    { name: 'Высокий', value: 8, color: '#ef4444' },
    { name: 'Средний', value: 15, color: '#f59e0b' },
    { name: 'Низкий', value: 12, color: '#10b981' },
  ];

  const userPerformance = users.map(u => ({
    name: u.name.split(' ')[0],
    tasks: u.tasksCompleted
  }));

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
        assignedTo: selectedUser || currentUser.name,
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

  const completedTasks = tasks.filter(t => t.completed).length;
  const totalTasks = tasks.length;
  const completionRate = Math.round((completedTasks / totalTasks) * 100);

  return (
    <div className="min-h-screen bg-gradient-to-br from-background via-background to-muted/20">
      <div className="flex">
        <aside className="w-64 min-h-screen bg-sidebar text-sidebar-foreground border-r border-sidebar-border">
          <div className="p-6">
            <h1 className="text-2xl font-bold text-primary mb-8">TaskFlow</h1>
            
            <nav className="space-y-2">
              {[
                { id: 'dashboard', icon: 'LayoutDashboard', label: 'Дашборд' },
                { id: 'tasks', icon: 'CheckSquare', label: 'Задачи' },
                { id: 'notes', icon: 'StickyNote', label: 'Заметки' },
                { id: 'analytics', icon: 'BarChart3', label: 'Аналитика' },
                { id: 'team', icon: 'Users', label: 'Команда' },
                { id: 'profile', icon: 'User', label: 'Профиль' },
              ].map(item => (
                <button
                  key={item.id}
                  onClick={() => setActiveTab(item.id)}
                  className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all ${
                    activeTab === item.id
                      ? 'bg-sidebar-accent text-primary'
                      : 'text-sidebar-foreground hover:bg-sidebar-accent/50'
                  }`}
                >
                  <Icon name={item.icon} size={20} />
                  <span className="font-medium">{item.label}</span>
                </button>
              ))}
            </nav>

            {currentUser.role === 'admin' && (
              <div className="mt-8 pt-8 border-t border-sidebar-border">
                <button
                  onClick={() => setActiveTab('admin')}
                  className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all ${
                    activeTab === 'admin'
                      ? 'bg-sidebar-accent text-primary'
                      : 'text-sidebar-foreground hover:bg-sidebar-accent/50'
                  }`}
                >
                  <Icon name="Shield" size={20} />
                  <span className="font-medium">Админка</span>
                </button>
              </div>
            )}
          </div>

          <div className="absolute bottom-0 left-0 right-0 p-6 border-t border-sidebar-border">
            <div className="flex items-center gap-3">
              <Avatar>
                <AvatarFallback className="bg-primary text-primary-foreground">
                  {currentUser.name.split(' ').map(n => n[0]).join('')}
                </AvatarFallback>
              </Avatar>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium truncate">{currentUser.name}</p>
                <p className="text-xs text-muted-foreground truncate">{currentUser.email}</p>
              </div>
            </div>
          </div>
        </aside>

        <main className="flex-1 p-8">
          {activeTab === 'dashboard' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Добро пожаловать, {currentUser.name.split(' ')[0]}! 👋</h2>
                <p className="text-muted-foreground">Вот что происходит с вашими задачами сегодня</p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card className="border-l-4 border-l-primary hover:shadow-lg transition-shadow">
                  <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Всего задач</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-3xl font-bold">{totalTasks}</div>
                    <p className="text-xs text-muted-foreground mt-1">+3 за эту неделю</p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-green-500 hover:shadow-lg transition-shadow">
                  <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Выполнено</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-3xl font-bold text-green-600">{completedTasks}</div>
                    <p className="text-xs text-muted-foreground mt-1">{completionRate}% от всех задач</p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-orange-500 hover:shadow-lg transition-shadow">
                  <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-medium text-muted-foreground">В работе</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-3xl font-bold text-orange-600">{totalTasks - completedTasks}</div>
                    <p className="text-xs text-muted-foreground mt-1">Требуют внимания</p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-blue-500 hover:shadow-lg transition-shadow">
                  <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Заметки</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-3xl font-bold text-blue-600">{notes.length}</div>
                    <p className="text-xs text-muted-foreground mt-1">{notes.filter(n => !n.completed).length} активных</p>
                  </CardContent>
                </Card>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="hover:shadow-lg transition-shadow">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Icon name="TrendingUp" size={20} />
                      Активность за неделю
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <ResponsiveContainer width="100%" height={250}>
                      <LineChart data={weeklyData}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                        <XAxis dataKey="day" stroke="#6b7280" />
                        <YAxis stroke="#6b7280" />
                        <Tooltip />
                        <Legend />
                        <Line type="monotone" dataKey="completed" stroke="#9b87f5" strokeWidth={3} name="Выполнено" />
                        <Line type="monotone" dataKey="created" stroke="#D6BCFA" strokeWidth={3} name="Создано" />
                      </LineChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Icon name="Target" size={20} />
                      Прогресс выполнения
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div>
                      <div className="flex justify-between mb-2">
                        <span className="text-sm font-medium">Общий прогресс</span>
                        <span className="text-sm font-bold text-primary">{completionRate}%</span>
                      </div>
                      <Progress value={completionRate} className="h-3" />
                    </div>
                    
                    <div className="grid grid-cols-3 gap-4 mt-6">
                      {priorityData.map(item => (
                        <div key={item.name} className="text-center">
                          <div className="text-2xl font-bold" style={{ color: item.color }}>{item.value}</div>
                          <div className="text-xs text-muted-foreground">{item.name}</div>
                        </div>
                      ))}
                    </div>

                    <div className="mt-6">
                      <ResponsiveContainer width="100%" height={120}>
                        <PieChart>
                          <Pie
                            data={priorityData}
                            cx="50%"
                            cy="50%"
                            innerRadius={30}
                            outerRadius={50}
                            paddingAngle={5}
                            dataKey="value"
                          >
                            {priorityData.map((entry, index) => (
                              <Cell key={`cell-${index}`} fill={entry.color} />
                            ))}
                          </Pie>
                        </PieChart>
                      </ResponsiveContainer>
                    </div>
                  </CardContent>
                </Card>
              </div>

              <Card className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Icon name="ListTodo" size={20} />
                    Последние задачи
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {tasks.slice(0, 4).map(task => (
                      <div key={task.id} className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors">
                        <Checkbox checked={task.completed} onCheckedChange={() => toggleTask(task.id)} />
                        <div className="flex-1">
                          <p className={`font-medium ${task.completed ? 'line-through text-muted-foreground' : ''}`}>
                            {task.title}
                          </p>
                          <p className="text-xs text-muted-foreground">{task.assignedTo}</p>
                        </div>
                        <Badge variant={task.priority === 'high' ? 'destructive' : task.priority === 'medium' ? 'default' : 'secondary'}>
                          {task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
                        </Badge>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'tasks' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Задачи</h2>
                <p className="text-muted-foreground">Управляйте своими задачами эффективно</p>
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Создать новую задачу</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex gap-3">
                    <Input
                      placeholder="Название задачи..."
                      value={newTask}
                      onChange={(e) => setNewTask(e.target.value)}
                      onKeyPress={(e) => e.key === 'Enter' && addTask()}
                      className="flex-1"
                    />
                    <Button onClick={addTask}>
                      <Icon name="Plus" size={18} />
                      Добавить
                    </Button>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Все задачи ({tasks.length})</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {tasks.map(task => (
                      <div key={task.id} className="flex items-center gap-3 p-4 bg-card border rounded-lg hover:shadow-md transition-all">
                        <Checkbox checked={task.completed} onCheckedChange={() => toggleTask(task.id)} />
                        <div className="flex-1">
                          <p className={`font-medium ${task.completed ? 'line-through text-muted-foreground' : ''}`}>
                            {task.title}
                          </p>
                          <p className="text-sm text-muted-foreground mt-1">
                            <Icon name="User" size={14} className="inline mr-1" />
                            {task.assignedTo}
                          </p>
                        </div>
                        <Badge variant={task.priority === 'high' ? 'destructive' : task.priority === 'medium' ? 'default' : 'secondary'}>
                          {task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
                        </Badge>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'notes' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Заметки</h2>
                <p className="text-muted-foreground">Ваши личные заметки и напоминания</p>
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Создать заметку</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex gap-3">
                    <Input
                      placeholder="Текст заметки..."
                      value={newNote}
                      onChange={(e) => setNewNote(e.target.value)}
                      onKeyPress={(e) => e.key === 'Enter' && addNote()}
                      className="flex-1"
                    />
                    <Button onClick={addNote}>
                      <Icon name="Plus" size={18} />
                      Добавить
                    </Button>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Мои заметки ({notes.length})</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {notes.map(note => (
                      <div key={note.id} className="flex items-center gap-3 p-4 bg-card border rounded-lg hover:shadow-md transition-all">
                        <Checkbox checked={note.completed} onCheckedChange={() => toggleNote(note.id)} />
                        <p className={`flex-1 ${note.completed ? 'line-through text-muted-foreground' : ''}`}>
                          {note.text}
                        </p>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'analytics' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Аналитика</h2>
                <p className="text-muted-foreground">Детальная статистика и метрики</p>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Производительность команды</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <ResponsiveContainer width="100%" height={300}>
                      <BarChart data={userPerformance}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                        <XAxis dataKey="name" stroke="#6b7280" />
                        <YAxis stroke="#6b7280" />
                        <Tooltip />
                        <Bar dataKey="tasks" fill="#9b87f5" radius={[8, 8, 0, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Распределение по приоритетам</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <ResponsiveContainer width="100%" height={300}>
                      <PieChart>
                        <Pie
                          data={priorityData}
                          cx="50%"
                          cy="50%"
                          labelLine={false}
                          label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                          outerRadius={100}
                          dataKey="value"
                        >
                          {priorityData.map((entry, index) => (
                            <Cell key={`cell-${index}`} fill={entry.color} />
                          ))}
                        </Pie>
                        <Tooltip />
                      </PieChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>
              </div>
            </div>
          )}

          {activeTab === 'team' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Команда</h2>
                <p className="text-muted-foreground">Все участники проекта</p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {users.map(user => (
                  <Card key={user.id} className="hover:shadow-lg transition-shadow">
                    <CardContent className="pt-6">
                      <div className="flex flex-col items-center text-center">
                        <Avatar className="w-20 h-20 mb-4">
                          <AvatarFallback className="bg-primary text-primary-foreground text-2xl">
                            {user.name.split(' ').map(n => n[0]).join('')}
                          </AvatarFallback>
                        </Avatar>
                        <h3 className="font-bold text-lg">{user.name}</h3>
                        <p className="text-sm text-muted-foreground mb-3">{user.email}</p>
                        <Badge variant={user.role === 'admin' ? 'default' : 'secondary'}>
                          {user.role === 'admin' ? 'Администратор' : 'Пользователь'}
                        </Badge>
                        <div className="mt-4 pt-4 border-t w-full">
                          <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Задач выполнено</span>
                            <span className="font-bold text-primary">{user.tasksCompleted}</span>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'profile' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Профиль</h2>
                <p className="text-muted-foreground">Управление вашим аккаунтом</p>
              </div>

              <Card>
                <CardContent className="pt-6">
                  <div className="flex items-start gap-6">
                    <Avatar className="w-24 h-24">
                      <AvatarFallback className="bg-primary text-primary-foreground text-3xl">
                        {currentUser.name.split(' ').map(n => n[0]).join('')}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1">
                      <h3 className="text-2xl font-bold mb-2">{currentUser.name}</h3>
                      <p className="text-muted-foreground mb-4">{currentUser.email}</p>
                      <Badge>{currentUser.role === 'admin' ? 'Администратор' : 'Пользователь'}</Badge>
                      
                      <div className="grid grid-cols-3 gap-4 mt-6">
                        <div className="p-4 bg-muted rounded-lg">
                          <div className="text-2xl font-bold text-primary">{currentUser.tasksCompleted}</div>
                          <div className="text-sm text-muted-foreground">Задач выполнено</div>
                        </div>
                        <div className="p-4 bg-muted rounded-lg">
                          <div className="text-2xl font-bold text-primary">{tasks.filter(t => t.assignedTo === currentUser.name && !t.completed).length}</div>
                          <div className="text-sm text-muted-foreground">Активных задач</div>
                        </div>
                        <div className="p-4 bg-muted rounded-lg">
                          <div className="text-2xl font-bold text-primary">{notes.length}</div>
                          <div className="text-sm text-muted-foreground">Заметок</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'admin' && currentUser.role === 'admin' && (
            <div className="space-y-6 animate-fade-in">
              <div>
                <h2 className="text-3xl font-bold mb-2">Админ-панель</h2>
                <p className="text-muted-foreground">Управление задачами команды</p>
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Создать задачу для команды</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <Input
                    placeholder="Название задачи..."
                    value={newTask}
                    onChange={(e) => setNewTask(e.target.value)}
                  />
                  
                  <div>
                    <label className="text-sm font-medium mb-2 block">Назначить на:</label>
                    <select
                      className="w-full p-2 border rounded-md bg-background"
                      value={selectedUser}
                      onChange={(e) => setSelectedUser(e.target.value)}
                    >
                      <option value="">Выберите участника...</option>
                      {users.map(user => (
                        <option key={user.id} value={user.name}>{user.name}</option>
                      ))}
                    </select>
                  </div>

                  <Button onClick={addTask} className="w-full">
                    <Icon name="UserPlus" size={18} />
                    Создать и назначить задачу
                  </Button>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Статистика команды</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {users.map(user => (
                      <div key={user.id} className="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                        <div className="flex items-center gap-3">
                          <Avatar>
                            <AvatarFallback className="bg-primary text-primary-foreground">
                              {user.name.split(' ').map(n => n[0]).join('')}
                            </AvatarFallback>
                          </Avatar>
                          <div>
                            <p className="font-medium">{user.name}</p>
                            <p className="text-sm text-muted-foreground">
                              {tasks.filter(t => t.assignedTo === user.name && !t.completed).length} активных задач
                            </p>
                          </div>
                        </div>
                        <div className="text-right">
                          <p className="text-2xl font-bold text-primary">{user.tasksCompleted}</p>
                          <p className="text-xs text-muted-foreground">выполнено</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          )}
        </main>
      </div>
    </div>
  );
};

export default Index;
