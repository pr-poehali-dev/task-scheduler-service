import NotesTab from './tabs/NotesTab';
import TeamManagementTab from './tabs/TeamManagementTab';
import AnalyticsTab from './tabs/AnalyticsTab';
import ProfileTab from './tabs/ProfileTab';
import SettingsTab from './tabs/SettingsTab';

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
  onUpdateUser: (user: User) => void;
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
  onLogout,
  onUpdateUser
}: TabsContentProps) => {
  if (activeTab === 'notes') {
    return (
      <NotesTab
        notes={notes}
        addNote={addNote}
        toggleNote={toggleNote}
        deleteNote={deleteNote}
      />
    );
  }

  if (activeTab === 'team' && currentUser.role === 'admin') {
    return (
      <TeamManagementTab
        users={users}
        tasks={tasks}
        addTask={addTask}
        deleteTask={deleteTask}
      />
    );
  }

  if (activeTab === 'analytics' && currentUser.role === 'admin') {
    return (
      <AnalyticsTab
        users={users}
        tasks={tasks}
      />
    );
  }

  if (activeTab === 'profile') {
    return (
      <ProfileTab
        currentUser={currentUser}
        tasks={tasks}
        onLogout={onLogout}
        onUpdateUser={onUpdateUser}
      />
    );
  }

  if (activeTab === 'settings' && currentUser.role === 'admin') {
    return <SettingsTab />;
  }

  return null;
};

export default TabsContent;