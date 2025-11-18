import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Icon from '@/components/ui/icon';
import { BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

interface Task {
  id: number;
  title: string;
  completed: boolean;
  assignedTo: string[];
  priority: 'low' | 'medium' | 'high';
  urgent?: boolean;
  deadline?: string;
  createdBy?: string;
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
  addNote: (text: string) => void;
  toggleNote: (id: number) => void;
  deleteNote: (id: number) => void;
  addTask: (task: Omit<Task, 'id'>) => void;
  deleteTask: (id: number) => void;
  onLogout: () => void;
}

const TabsContent = ({
  activeTab,
  currentUser,
  users,
  tasks,
  notes,
  addNote,
  toggleNote,
  deleteNote,
  addTask,
  deleteTask,
  onLogout
}: TabsContentProps) => {
  const [newNote, setNewNote] = useState('');
  const [newTask, setNewTask] = useState('');
  const [selectedUsers, setSelectedUsers] = useState<string[]>([]);
  const [taskPriority, setTaskPriority] = useState<'low' | 'medium' | 'high'>('medium');
  const [taskDeadline, setTaskDeadline] = useState('');
  const [isUrgent, setIsUrgent] = useState(false);

  const handleAddTask = () => {
    if (newTask.trim() && selectedUsers.length > 0) {
      addTask({
        title: newTask,
        completed: false,
        assignedTo: selectedUsers,
        priority: taskPriority,
        urgent: isUrgent,
        deadline: taskDeadline || undefined,
      });
      
      setNewTask('');
      setSelectedUsers([]);
      setTaskPriority('medium');
      setTaskDeadline('');
      setIsUrgent(false);
    }
  };

  const handleAddNote = () => {
    if (newNote.trim()) {
      addNote(newNote);
      setNewNote('');
    }
  };

  const toggleUserSelection = (userName: string) => {
    if (selectedUsers.includes(userName)) {
      setSelectedUsers(selectedUsers.filter(u => u !== userName));
    } else {
      setSelectedUsers([...selectedUsers, userName]);
    }
  };

  const priorityData = [
    { name: 'Высокий', value: tasks.filter(t => t.priority === 'high').length, color: '#ef4444' },
    { name: 'Средний', value: tasks.filter(t => t.priority === 'medium').length, color: '#f59e0b' },
    { name: 'Низкий', value: tasks.filter(t => t.priority === 'low').length, color: '#10b981' },
  ];

  const userPerformance = users.map(u => ({
    name: u.name.split(' ')[0],
    tasks: tasks.filter(t => t.assignedTo.includes(u.name) && t.completed).length
  }));

  const isOverdue = (deadline?: string) => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
  };

  const isDueSoon = (deadline?: string) => {
    if (!deadline) return false;
    const daysUntil = Math.ceil((new Date(deadline).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    return daysUntil <= 3 && daysUntil >= 0;
  };

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
                onKeyPress={(e) => e.key === 'Enter' && handleAddNote()}
                className="flex-1"
              />
              <Button onClick={handleAddNote}>
                <Icon name="Plus" size={18} />
                Добавить
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Все заметки ({notes.length})</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {notes.map(note => (
                <div key={note.id} className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors">
                  <Checkbox
                    checked={note.completed}
                    onCheckedChange={() => toggleNote(note.id)}
                  />
                  <span className={`flex-1 ${note.completed ? 'line-through text-muted-foreground' : ''}`}>
                    {note.text}
                  </span>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => deleteNote(note.id)}
                    className="text-destructive hover:text-destructive"
                  >
                    <Icon name="Trash2" size={16} />
                  </Button>
                </div>
              ))}
              {notes.length === 0 && (
                <p className="text-center text-muted-foreground py-8">Пока нет заметок</p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (activeTab === 'team' && currentUser.role === 'admin') {
    const overdueTasks = tasks.filter(t => !t.completed && isOverdue(t.deadline));

    return (
      <div className="space-y-6 animate-fade-in">
        <div>
          <h2 className="text-3xl font-bold mb-2">Управление командой</h2>
          <p className="text-muted-foreground">Создавайте задачи и отслеживайте прогресс</p>
        </div>

        {overdueTasks.length > 0 && (
          <Card className="border-l-4 border-l-red-500 bg-red-50 dark:bg-red-950/20">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                <Icon name="AlertTriangle" size={20} />
                Просроченные задачи ({overdueTasks.length})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {overdueTasks.map(task => (
                  <div key={task.id} className="bg-background/50 p-3 rounded-lg">
                    <p className="font-medium">{task.title}</p>
                    <p className="text-sm text-muted-foreground">
                      Назначено: {task.assignedTo.join(', ')} • Срок: {task.deadline}
                    </p>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Создать задачу для команды</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="task-title">Название задачи</Label>
              <Input
                id="task-title"
                placeholder="Введите название задачи..."
                value={newTask}
                onChange={(e) => setNewTask(e.target.value)}
                className="mt-1"
              />
            </div>

            <div>
              <Label>Назначить сотрудников</Label>
              <div className="grid grid-cols-2 gap-2 mt-2">
                {users.filter(u => u.role !== 'admin').map(user => (
                  <div
                    key={user.id}
                    onClick={() => toggleUserSelection(user.name)}
                    className={`flex items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all ${
                      selectedUsers.includes(user.name)
                        ? 'border-primary bg-primary/10'
                        : 'border-muted hover:border-primary/50'
                    }`}
                  >
                    <Checkbox
                      checked={selectedUsers.includes(user.name)}
                      onCheckedChange={() => toggleUserSelection(user.name)}
                    />
                    <Avatar className="h-8 w-8">
                      <AvatarFallback className="text-xs">
                        {user.name.split(' ').map(n => n[0]).join('')}
                      </AvatarFallback>
                    </Avatar>
                    <span className="text-sm font-medium">{user.name}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="task-priority">Приоритет</Label>
                <Select value={taskPriority} onValueChange={(v: any) => setTaskPriority(v)}>
                  <SelectTrigger id="task-priority" className="mt-1">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Низкий</SelectItem>
                    <SelectItem value="medium">Средний</SelectItem>
                    <SelectItem value="high">Высокий</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label htmlFor="task-deadline">Срок выполнения</Label>
                <Input
                  id="task-deadline"
                  type="date"
                  value={taskDeadline}
                  onChange={(e) => setTaskDeadline(e.target.value)}
                  className="mt-1"
                />
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Checkbox
                id="task-urgent"
                checked={isUrgent}
                onCheckedChange={(checked) => setIsUrgent(checked as boolean)}
              />
              <Label htmlFor="task-urgent" className="cursor-pointer">
                Срочная задача 🔥
              </Label>
            </div>

            <Button onClick={handleAddTask} className="w-full" size="lg">
              <Icon name="Plus" size={18} />
              Создать задачу
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
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex-1">
                          <p className="font-medium">{task.title}</p>
                          {task.urgent && (
                            <Badge variant="destructive" className="mt-1">🔥 СРОЧНО</Badge>
                          )}
                        </div>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => deleteTask(task.id)}
                          className="text-destructive hover:text-destructive"
                        >
                          <Icon name="Trash2" size={16} />
                        </Button>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Icon name="Users" size={12} />
                        {task.assignedTo.join(', ')}
                      </div>
                      {task.deadline && (
                        <div className={`flex items-center gap-2 text-sm mt-1 ${
                          isOverdue(task.deadline) ? 'text-red-600' : isDueSoon(task.deadline) ? 'text-orange-600' : 'text-muted-foreground'
                        }`}>
                          <Icon name="Calendar" size={12} />
                          Срок: {task.deadline}
                          {isOverdue(task.deadline) && ' (просрочено)'}
                          {isDueSoon(task.deadline) && ' (скоро истекает)'}
                        </div>
                      )}
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
                          <div className="flex items-center gap-2 mt-1">
                            <Badge variant="outline" className="text-green-600 border-green-600">✓ Выполнено</Badge>
                          </div>
                          <p className="text-sm text-muted-foreground mt-1">
                            <Icon name="Users" size={12} className="inline mr-1" />
                            {task.assignedTo.join(', ')}
                          </p>
                        </div>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => deleteTask(task.id)}
                          className="text-destructive hover:text-destructive"
                        >
                          <Icon name="Trash2" size={16} />
                        </Button>
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
              {users.map(user => {
                const userTasks = tasks.filter(t => t.assignedTo.includes(user.name));
                const completedCount = userTasks.filter(t => t.completed).length;
                const activeCount = userTasks.filter(t => !t.completed).length;
                
                return (
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
                          {activeCount} активных задач
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-2xl font-bold text-primary">{completedCount}</p>
                      <p className="text-xs text-muted-foreground">выполнено</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (activeTab === 'analytics' && currentUser.role === 'admin') {
    return (
      <div className="space-y-6 animate-fade-in">
        <div>
          <h2 className="text-3xl font-bold mb-2">Аналитика</h2>
          <p className="text-muted-foreground">Детальная статистика и графики</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    label={({ name, value }) => `${name}: ${value}`}
                    outerRadius={80}
                    fill="#8884d8"
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

          <Card>
            <CardHeader>
              <CardTitle>Производительность команды</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={userPerformance}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="name" />
                  <YAxis />
                  <Tooltip />
                  <Bar dataKey="tasks" fill="hsl(var(--primary))" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  if (activeTab === 'profile') {
    return (
      <div className="space-y-6 animate-fade-in">
        <div>
          <h2 className="text-3xl font-bold mb-2">Профиль</h2>
          <p className="text-muted-foreground">Информация о вашем аккаунте</p>
        </div>

        <Card>
          <CardHeader>
            <div className="flex items-center gap-4">
              <Avatar className="h-20 w-20">
                <AvatarFallback className="bg-primary text-primary-foreground text-2xl">
                  {currentUser.name.split(' ').map(n => n[0]).join('')}
                </AvatarFallback>
              </Avatar>
              <div>
                <h3 className="text-2xl font-bold">{currentUser.name}</h3>
                <p className="text-muted-foreground">{currentUser.email}</p>
                <Badge variant={currentUser.role === 'admin' ? 'default' : 'secondary'} className="mt-2">
                  {currentUser.role === 'admin' ? 'Администратор' : 'Сотрудник'}
                </Badge>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-4 p-4 bg-muted/50 rounded-lg">
              <div>
                <p className="text-sm text-muted-foreground">Всего задач</p>
                <p className="text-2xl font-bold">{tasks.filter(t => t.assignedTo.includes(currentUser.name)).length}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Выполнено</p>
                <p className="text-2xl font-bold text-green-600">
                  {tasks.filter(t => t.assignedTo.includes(currentUser.name) && t.completed).length}
                </p>
              </div>
            </div>
            
            <Button onClick={onLogout} variant="destructive" className="w-full">
              <Icon name="LogOut" size={18} />
              Выйти из аккаунта
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return null;
};

export default TabsContent;
