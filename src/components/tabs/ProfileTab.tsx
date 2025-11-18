import { useState } from 'react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';
import EditProfileModal from '../EditProfileModal';
import TelegramConnect from '../TelegramConnect';
import TelegramSetupGuide from '../TelegramSetupGuide';

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
  telegramChatId?: number;
}

interface ProfileTabProps {
  currentUser: User;
  tasks: Task[];
  onLogout: () => void;
  onUpdateUser: (user: User) => void;
}

const ProfileTab = ({ currentUser, tasks, onLogout, onUpdateUser }: ProfileTabProps) => {
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);

  return (
    <>
      <EditProfileModal
        user={currentUser}
        isOpen={isEditModalOpen}
        onClose={() => setIsEditModalOpen(false)}
        onSave={onUpdateUser}
      />
    <div className="space-y-6 animate-fade-in">
      <div>
        <h2 className="text-3xl font-bold mb-2">Профиль</h2>
        <p className="text-muted-foreground">Информация о вашем аккаунте</p>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <Avatar className="h-20 w-20">
              {currentUser.avatar ? (
                <AvatarImage src={currentUser.avatar} alt={currentUser.name} />
              ) : null}
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

          <Button onClick={() => setIsEditModalOpen(true)} variant="outline" className="w-full">
            <Icon name="Edit" size={18} />
            Редактировать профиль
          </Button>
          
          <Button onClick={onLogout} variant="destructive" className="w-full">
            <Icon name="LogOut" size={18} />
            Выйти из аккаунта
          </Button>
        </CardContent>
      </Card>

      <TelegramConnect currentUser={currentUser} />
      
      <TelegramSetupGuide />
      </div>
    </>
  );
};

export default ProfileTab;