import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Icon from '@/components/ui/icon';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  tasksCompleted: number;
  avatar?: string;
}

interface EditProfileModalProps {
  user: User;
  isOpen: boolean;
  onClose: () => void;
  onSave: (updatedUser: User) => void;
}

const EditProfileModal = ({ user, isOpen, onClose, onSave }: EditProfileModalProps) => {
  const [name, setName] = useState(user.name);
  const [email, setEmail] = useState(user.email);
  const [avatar, setAvatar] = useState(user.avatar || '');
  const [isUploading, setIsUploading] = useState(false);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setIsUploading(true);
      const reader = new FileReader();
      reader.onloadend = () => {
        setAvatar(reader.result as string);
        setIsUploading(false);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSave = () => {
    onSave({
      ...user,
      name,
      email,
      avatar
    });
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Редактировать профиль</DialogTitle>
        </DialogHeader>

        <div className="space-y-6 mt-4">
          <div className="flex flex-col items-center gap-4">
            <Avatar className="h-24 w-24">
              {avatar ? (
                <AvatarImage src={avatar} alt={name} />
              ) : null}
              <AvatarFallback className="bg-primary text-primary-foreground text-3xl">
                {name.split(' ').map(n => n[0]).join('')}
              </AvatarFallback>
            </Avatar>
            
            <div className="relative">
              <input
                type="file"
                id="avatar-upload"
                className="hidden"
                accept="image/*"
                onChange={handleFileChange}
                disabled={isUploading}
              />
              <Button
                variant="outline"
                size="sm"
                onClick={() => document.getElementById('avatar-upload')?.click()}
                disabled={isUploading}
              >
                <Icon name={isUploading ? "Loader2" : "Upload"} size={16} className={isUploading ? "animate-spin" : ""} />
                {isUploading ? 'Загрузка...' : 'Загрузить фото'}
              </Button>
            </div>
          </div>

          <div className="space-y-4">
            <div>
              <Label htmlFor="edit-name">Имя</Label>
              <Input
                id="edit-name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Введите имя"
                className="mt-1"
              />
            </div>

            <div>
              <Label htmlFor="edit-email">Email</Label>
              <Input
                id="edit-email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Введите email"
                className="mt-1"
              />
            </div>
          </div>

          <div className="flex gap-3">
            <Button onClick={handleSave} className="flex-1">
              <Icon name="Save" size={16} />
              Сохранить
            </Button>
            <Button onClick={onClose} variant="outline" className="flex-1">
              Отмена
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default EditProfileModal;
