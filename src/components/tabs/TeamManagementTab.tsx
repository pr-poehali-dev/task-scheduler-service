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

interface TeamManagementTabProps {
  users: User[];
  tasks: Task[];
  addTask: (task: Omit<Task, 'id'>) => void;
  deleteTask: (id: number) => void;
}

const TeamManagementTab = ({ users, tasks, addTask, deleteTask }: TeamManagementTabProps) => {
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

  const toggleUserSelection = (userName: string) => {
    if (selectedUsers.includes(userName)) {
      setSelectedUsers(selectedUsers.filter(u => u !== userName));
    } else {
      setSelectedUsers([...selectedUsers, userName]);
    }
  };

  const isOverdue = (deadline?: string) => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
  };

  const isDueSoon = (deadline?: string) => {
    if (!deadline) return false;
    const daysUntil = Math.ceil((new Date(deadline).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    return daysUntil <= 3 && daysUntil >= 0;
  };

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
};

export default TeamManagementTab;
