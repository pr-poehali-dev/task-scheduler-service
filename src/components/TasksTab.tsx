import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';
import TaskDetailModal from './TaskDetailModal';

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
  avatar?: string;
}

interface TasksTabProps {
  currentUser: User;
  tasks: Task[];
  users: User[];
  toggleTask: (id: number) => void;
  deleteTask: (id: number) => void;
}

const TasksTab = ({ currentUser, tasks, users, toggleTask, deleteTask }: TasksTabProps) => {
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const myTasks = tasks.filter(t => t.assignedTo.includes(currentUser.name));

  const handleTaskClick = (task: Task) => {
    setSelectedTask(task);
    setIsModalOpen(true);
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

  const activeTasks = myTasks.filter(t => !t.completed);
  const completedTasks = myTasks.filter(t => t.completed);

  return (
    <>
      <TaskDetailModal
        task={selectedTask}
        users={users}
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
      />

      <div className="space-y-6 animate-fade-in">
      <div>
        <h2 className="text-3xl font-bold mb-2">Мои задачи</h2>
        <p className="text-muted-foreground">Только задачи, назначенные вам администратором</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Активные задачи ({activeTasks.length})</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {activeTasks.length === 0 ? (
              <p className="text-center text-muted-foreground py-8">Все задачи выполнены! 🎉</p>
            ) : (
              activeTasks.map(task => (
                <div 
                  key={task.id} 
                  className="flex items-start gap-3 p-4 bg-card border rounded-lg hover:shadow-md transition-all cursor-pointer"
                  onClick={() => handleTaskClick(task)}
                >
                  <Checkbox 
                    checked={task.completed} 
                    onCheckedChange={(e) => {
                      e.stopPropagation?.();
                      toggleTask(task.id);
                    }}
                    onClick={(e) => e.stopPropagation()}
                    className="mt-1"
                  />
                  <div className="flex-1">
                    <p className="font-medium">{task.title}</p>
                    
                    <div className="flex flex-wrap items-center gap-2 mt-2">
                      {task.urgent && (
                        <Badge variant="destructive">🔥 СРОЧНО</Badge>
                      )}
                      <Badge variant={
                        task.priority === 'high' ? 'destructive' : 
                        task.priority === 'medium' ? 'default' : 
                        'secondary'
                      }>
                        {task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
                      </Badge>
                      {task.deadline && (
                        <Badge variant="outline" className={
                          isOverdue(task.deadline) ? 'border-red-500 text-red-600' :
                          isDueSoon(task.deadline) ? 'border-orange-500 text-orange-600' :
                          'border-muted-foreground'
                        }>
                          <Icon name="Calendar" size={12} className="mr-1" />
                          {task.deadline}
                          {isOverdue(task.deadline) && ' (просрочено)'}
                          {isDueSoon(task.deadline) && ' (скоро)'}
                        </Badge>
                      )}
                    </div>

                    {task.assignedTo.length > 1 && (
                      <p className="text-sm text-muted-foreground mt-2">
                        <Icon name="Users" size={14} className="inline mr-1" />
                        Совместно с: {task.assignedTo.filter(u => u !== currentUser.name).join(', ')}
                      </p>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Выполненные задачи ({completedTasks.length})</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {completedTasks.length === 0 ? (
              <p className="text-center text-muted-foreground py-8">Пока нет выполненных задач</p>
            ) : (
              completedTasks.map(task => (
                <div key={task.id} className="p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                  <div className="flex items-start gap-3">
                    <Icon name="CheckCircle2" size={20} className="text-green-600 mt-1" />
                    <div className="flex-1">
                      <p className="font-medium line-through text-muted-foreground">{task.title}</p>
                      <div className="flex items-center gap-2 mt-2">
                        <Badge variant="outline" className="text-green-600 border-green-600">
                          ✓ Выполнено
                        </Badge>
                        {task.deadline && (
                          <span className="text-sm text-muted-foreground">
                            Срок был: {task.deadline}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>
      </div>
    </>
  );
};

export default TasksTab;