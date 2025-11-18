import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  tasksCompleted: number;
  password: string;
}

interface AuthScreenProps {
  onLogin: (user: User) => void;
  registeredUsers: User[];
  onRegister: (user: User) => void;
}

const AuthScreen = ({ onLogin, registeredUsers, onRegister }: AuthScreenProps) => {
  const [isLogin, setIsLogin] = useState(true);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [error, setError] = useState('');

  const handleLogin = () => {
    const user = registeredUsers.find(u => u.email === email && u.password === password);
    if (user) {
      onLogin(user);
    } else {
      setError('Неверный email или пароль');
    }
  };

  const handleRegister = () => {
    if (!name || !email || !password) {
      setError('Заполните все поля');
      return;
    }

    if (registeredUsers.find(u => u.email === email)) {
      setError('Пользователь с таким email уже существует');
      return;
    }

    const newUser: User = {
      id: registeredUsers.length + 1,
      name,
      email,
      password,
      role: 'user',
      tasksCompleted: 0
    };

    onRegister(newUser);
    onLogin(newUser);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex items-center justify-center p-4">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="text-3xl font-bold text-center">
            {isLogin ? 'Вход в TaskFlow' : 'Регистрация'}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {!isLogin && (
            <div className="space-y-2">
              <Label htmlFor="name">Имя</Label>
              <Input
                id="name"
                placeholder="Иван Иванов"
                value={name}
                onChange={(e) => setName(e.target.value)}
              />
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              placeholder="ivan@company.ru"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="password">Пароль</Label>
            <Input
              id="password"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && (isLogin ? handleLogin() : handleRegister())}
            />
          </div>

          {error && (
            <p className="text-sm text-destructive">{error}</p>
          )}

          <Button
            onClick={isLogin ? handleLogin : handleRegister}
            className="w-full"
          >
            {isLogin ? 'Войти' : 'Зарегистрироваться'}
          </Button>

          <div className="text-center">
            <button
              onClick={() => {
                setIsLogin(!isLogin);
                setError('');
              }}
              className="text-sm text-primary hover:underline"
            >
              {isLogin ? 'Нет аккаунта? Зарегистрируйтесь' : 'Уже есть аккаунт? Войдите'}
            </button>
          </div>

          {isLogin && (
            <div className="pt-4 border-t">
              <p className="text-xs text-muted-foreground mb-2">Тестовые аккаунты:</p>
              <p className="text-xs text-muted-foreground">Админ: alex@company.ru / admin123</p>
              <p className="text-xs text-muted-foreground">Юзер: maria@company.ru / user123</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default AuthScreen;
