import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import Icon from '@/components/ui/icon';
import { LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

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

interface DashboardTabProps {
  currentUser: User;
  tasks: Task[];
  notes: Array<{ id: number; text: string; completed: boolean }>;
  toggleTask: (id: number) => void;
  dismissedNotifications: number[];
  dismissNotification: (taskId: number) => void;
}

const DashboardTab = ({ 
  currentUser, 
  tasks, 
  notes, 
  toggleTask,
  dismissedNotifications,
  dismissNotification
}: DashboardTabProps) => {
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
    { name: 'Высокий', value: tasks.filter(t => t.priority === 'high').length, color: '#ef4444' },
    { name: 'Средний', value: tasks.filter(t => t.priority === 'medium').length, color: '#f59e0b' },
    { name: 'Низкий', value: tasks.filter(t => t.priority === 'low').length, color: '#10b981' },
  ];

  const myTasks = tasks.filter(t => t.assignedTo.includes(currentUser.name));
  const completedTasks = myTasks.filter(t => t.completed).length;
  const totalTasks = myTasks.length;
  const completionRate = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

  const newAssignedTasks = myTasks.filter(t => 
    !t.completed && 
    t.createdBy !== currentUser.name && 
    !dismissedNotifications.includes(t.id)
  );

  const isOverdue = (deadline?: string) => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
  };

  const isDueSoon = (deadline?: string) => {
    if (!deadline) return false;
    const daysUntil = Math.ceil((new Date(deadline).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    return daysUntil <= 3 && daysUntil >= 0;
  };

  const overdueTasks = myTasks.filter(t => !t.completed && isOverdue(t.deadline));
  const dueSoonTasks = myTasks.filter(t => !t.completed && isDueSoon(t.deadline));

  return (
    <div className="space-y-6 animate-fade-in">
      {newAssignedTasks.length > 0 && (
        <Card className="border-l-4 border-l-orange-500 bg-orange-50 dark:bg-orange-950/20">
          <CardContent className="pt-6">
            <div className="flex items-start justify-between gap-3">
              <div className="flex items-start gap-3 flex-1">
                <Icon name="Bell" size={24} className="text-orange-600 mt-1" />
                <div className="flex-1">
                  <h3 className="font-bold text-lg mb-1">У вас {newAssignedTasks.length} {newAssignedTasks.length === 1 ? 'новая задача' : 'новых задачи'}</h3>
                  <p className="text-sm text-muted-foreground mb-3">Вам назначены новые задачи для выполнения</p>
                  <div className="space-y-2">
                    {newAssignedTasks.slice(0, 3).map(task => (
                      <div key={task.id} className="text-sm bg-background/50 p-2 rounded flex items-center justify-between">
                        <span>• {task.title}</span>
                        {task.urgent && <Badge variant="destructive" className="ml-2">СРОЧНО</Badge>}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => newAssignedTasks.forEach(t => dismissNotification(t.id))}
              >
                <Icon name="X" size={18} />
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {overdueTasks.length > 0 && (
        <Card className="border-l-4 border-l-red-500 bg-red-50 dark:bg-red-950/20">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <Icon name="AlertCircle" size={24} className="text-red-600 mt-1" />
              <div>
                <h3 className="font-bold text-lg mb-1 text-red-700">Просроченные задачи ({overdueTasks.length})</h3>
                <p className="text-sm text-muted-foreground mb-3">Эти задачи требуют срочного внимания</p>
                <div className="space-y-2">
                  {overdueTasks.map(task => (
                    <div key={task.id} className="text-sm bg-background/50 p-2 rounded">
                      • {task.title} - срок: {task.deadline}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {dueSoonTasks.length > 0 && !overdueTasks.length && (
        <Card className="border-l-4 border-l-yellow-500 bg-yellow-50 dark:bg-yellow-950/20">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <Icon name="Clock" size={24} className="text-yellow-600 mt-1" />
              <div>
                <h3 className="font-bold text-lg mb-1 text-yellow-700">Срок истекает ({dueSoonTasks.length})</h3>
                <p className="text-sm text-muted-foreground mb-3">Эти задачи нужно выполнить в ближайшие 3 дня</p>
                <div className="space-y-2">
                  {dueSoonTasks.map(task => (
                    <div key={task.id} className="text-sm bg-background/50 p-2 rounded">
                      • {task.title} - срок: {task.deadline}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

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
            <p className="text-xs text-muted-foreground mt-1">Назначено вам</p>
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
            Мои задачи
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {myTasks.length === 0 ? (
              <p className="text-center text-muted-foreground py-8">Пока нет назначенных задач</p>
            ) : (
              myTasks.slice(0, 6).map(task => (
                <div key={task.id} className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors">
                  <Checkbox checked={task.completed} onCheckedChange={() => toggleTask(task.id)} />
                  <div className="flex-1">
                    <span className={task.completed ? 'line-through text-muted-foreground' : ''}>
                      {task.title}
                    </span>
                    <div className="flex items-center gap-2 mt-1">
                      {task.urgent && <Badge variant="destructive" className="text-xs">СРОЧНО</Badge>}
                      {task.completed && <Badge variant="outline" className="text-xs text-green-600 border-green-600">✓ Выполнено</Badge>}
                      <Badge variant={
                        task.priority === 'high' ? 'destructive' : 
                        task.priority === 'medium' ? 'default' : 
                        'secondary'
                      } className="text-xs">
                        {task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
                      </Badge>
                      {task.deadline && (
                        <span className={`text-xs ${
                          isOverdue(task.deadline) ? 'text-red-600' : 
                          isDueSoon(task.deadline) ? 'text-orange-600' : 
                          'text-muted-foreground'
                        }`}>
                          {task.deadline}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default DashboardTab;
