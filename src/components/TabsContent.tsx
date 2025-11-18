import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';
import { BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

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

interface TabsContentProps {
  activeTab: string;
  currentUser: User;
  users: User[];
  tasks: Task[];
  notes: Array<{ id: number; text: string; completed: boolean }>;
  newNote: string;
  setNewNote: (value: string) => void;
  addNote: () => void;
  toggleNote: (id: number) => void;
  newTask: string;
  setNewTask: (value: string) => void;
  selectedUser: string;
  setSelectedUser: (value: string) => void;
  addTask: () => void;
  onLogout: () => void;
}

const TabsContent = ({
  activeTab,
  currentUser,
  users,
  tasks,
  notes,
  newNote,
  setNewNote,
  addNote,
  toggleNote,
  newTask,
  setNewTask,
  selectedUser,
  setSelectedUser,
  addTask,
  onLogout
}: TabsContentProps) => {
  const priorityData = [
    { name: 'Высокий', value: 8, color: '#ef4444' },
    { name: 'Средний', value: 15, color: '#f59e0b' },
    { name: 'Низкий', value: 12, color: '#10b981' },
  ];

  const userPerformance = users.map(u => ({
    name: u.name.split(' ')[0],
    tasks: u.tasksCompleted
  }));

  if (activeTab === 'notes') {
    return (
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
    );
  }

  if (activeTab === 'analytics') {
    return (
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
    );
  }

  if (activeTab === 'team') {
    return (
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
    );
  }

  if (activeTab === 'profile') {
    return (
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

                <Button
                  onClick={onLogout}
                  variant="destructive"
                  className="mt-6 w-full"
                >
                  <Icon name="LogOut" size={18} />
                  Выйти из системы
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (activeTab === 'admin' && currentUser.role === 'admin') {
    return (
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

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Icon name="Clock" size={20} />
                Задачи в работе
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {tasks.filter(t => !t.completed).length === 0 ? (
                  <p className="text-sm text-muted-foreground text-center py-4">Все задачи выполнены! 🎉</p>
                ) : (
                  tasks.filter(t => !t.completed).map(task => (
                    <div key={task.id} className="p-3 bg-muted/50 rounded-lg">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <p className="font-medium">{task.title}</p>
                          <p className="text-sm text-muted-foreground mt-1">
                            <Icon name="User" size={12} className="inline mr-1" />
                            {task.assignedTo}
                          </p>
                        </div>
                        <Badge variant={task.priority === 'high' ? 'destructive' : task.priority === 'medium' ? 'default' : 'secondary'}>
                          {task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
                        </Badge>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Icon name="CheckCircle2" size={20} />
                Выполненные задачи
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {tasks.filter(t => t.completed).length === 0 ? (
                  <p className="text-sm text-muted-foreground text-center py-4">Пока нет выполненных задач</p>
                ) : (
                  tasks.filter(t => t.completed).map(task => (
                    <div key={task.id} className="p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <p className="font-medium line-through text-muted-foreground">{task.title}</p>
                          <p className="text-sm text-muted-foreground mt-1">
                            <Icon name="User" size={12} className="inline mr-1" />
                            {task.assignedTo}
                          </p>
                        </div>
                        <Icon name="CheckCircle2" size={20} className="text-green-600" />
                      </div>
                    </div>
                  ))
                )}
              </div>
            </CardContent>
          </Card>
        </div>

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
                    <p className="text-2xl font-bold text-primary">{tasks.filter(t => t.assignedTo === user.name && t.completed).length}</p>
                    <p className="text-xs text-muted-foreground">выполнено</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return null;
};

export default TabsContent;