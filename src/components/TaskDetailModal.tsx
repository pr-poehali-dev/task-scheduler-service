import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
  avatar?: string;
}

interface TaskDetailModalProps {
  task: Task | null;
  users: User[];
  isOpen: boolean;
  onClose: () => void;
}

const TaskDetailModal = ({ task, users, isOpen, onClose }: TaskDetailModalProps) => {
  if (!task) return null;

  const isOverdue = (deadline?: string) => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
  };

  const isDueSoon = (deadline?: string) => {
    if (!deadline) return false;
    const daysUntil = Math.ceil((new Date(deadline).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    return daysUntil <= 3 && daysUntil >= 0;
  };

  const assignedUsers = users.filter(u => task.assignedTo.includes(u.name));

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle className="text-2xl">{task.title}</DialogTitle>
        </DialogHeader>

        <div className="space-y-6 mt-4">
          <div className="flex flex-wrap gap-2">
            {task.urgent && (
              <Badge variant="destructive" className="text-base">
                <Icon name="Flame" size={16} className="mr-1" />
                СРОЧНО
              </Badge>
            )}
            <Badge variant={
              task.priority === 'high' ? 'destructive' : 
              task.priority === 'medium' ? 'default' : 
              'secondary'
            } className="text-base">
              Приоритет: {task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
            </Badge>
            {task.completed && (
              <Badge variant="outline" className="text-base text-green-600 border-green-600">
                <Icon name="CheckCircle2" size={16} className="mr-1" />
                Выполнено
              </Badge>
            )}
          </div>

          {task.deadline && (
            <div className="bg-muted/50 p-4 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Icon name="Calendar" size={20} className={
                  isOverdue(task.deadline) ? 'text-red-600' :
                  isDueSoon(task.deadline) ? 'text-orange-600' :
                  'text-muted-foreground'
                } />
                <h3 className="font-semibold">Срок выполнения</h3>
              </div>
              <p className={`text-lg ${
                isOverdue(task.deadline) ? 'text-red-600 font-bold' :
                isDueSoon(task.deadline) ? 'text-orange-600 font-semibold' :
                'text-foreground'
              }`}>
                {task.deadline}
                {isOverdue(task.deadline) && ' (Просрочено!)'}
                {isDueSoon(task.deadline) && ' (Скоро истекает)'}
              </p>
            </div>
          )}

          <div className="bg-muted/50 p-4 rounded-lg">
            <div className="flex items-center gap-2 mb-3">
              <Icon name="Users" size={20} />
              <h3 className="font-semibold">Назначено ({assignedUsers.length})</h3>
            </div>
            <div className="space-y-3">
              {assignedUsers.map(user => (
                <div key={user.id} className="flex items-center gap-3 p-2 bg-background rounded-lg">
                  <Avatar className="h-10 w-10">
                    {user.avatar ? (
                      <AvatarImage src={user.avatar} alt={user.name} />
                    ) : null}
                    <AvatarFallback className="bg-primary text-primary-foreground">
                      {user.name.split(' ').map(n => n[0]).join('')}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <p className="font-medium">{user.name}</p>
                    <p className="text-sm text-muted-foreground">{user.email}</p>
                  </div>
                  <Badge variant={user.role === 'admin' ? 'default' : 'secondary'}>
                    {user.role === 'admin' ? 'Администратор' : 'Сотрудник'}
                  </Badge>
                </div>
              ))}
            </div>
          </div>

          {task.createdBy && (
            <div className="text-sm text-muted-foreground flex items-center gap-2">
              <Icon name="UserPlus" size={16} />
              Создано: {task.createdBy}
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default TaskDetailModal;
