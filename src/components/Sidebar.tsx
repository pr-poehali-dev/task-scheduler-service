import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import Icon from '@/components/ui/icon';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  tasksCompleted: number;
}

interface SidebarProps {
  currentUser: User;
  activeTab: string;
  setActiveTab: (tab: string) => void;
}

const Sidebar = ({ currentUser, activeTab, setActiveTab }: SidebarProps) => {
  return (
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
  );
};

export default Sidebar;
