import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import Icon from '@/components/ui/icon';

interface NotesTabProps {
  notes: Array<{ id: number; text: string; completed: boolean }>;
  addNote: (text: string) => void;
  toggleNote: (id: number) => void;
  deleteNote: (id: number) => void;
}

const NotesTab = ({ notes, addNote, toggleNote, deleteNote }: NotesTabProps) => {
  const [newNote, setNewNote] = useState('');

  const handleAddNote = () => {
    if (newNote.trim()) {
      addNote(newNote);
      setNewNote('');
    }
  };

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
};

export default NotesTab;
