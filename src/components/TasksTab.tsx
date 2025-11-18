import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';

interface Task {
  id: number;
  title: string;
  completed: boolean;
  assignedTo?: string;
  priority: 'low' | 'medium' | 'high';
}

interface TasksTabProps {
  tasks: Task[];
  newTask: string;
  setNewTask: (value: string) => void;
  addTask: () => void;
  toggleTask: (id: number) => void;
}

const TasksTab = ({ tasks, newTask, setNewTask, addTask, toggleTask }: TasksTabProps) => {
  return (
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
  );
};

export default TasksTab;
