import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';
import { TELEGRAM_CONFIG } from '@/config/telegram';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  tasksCompleted: number;
  avatar?: string;
  telegramChatId?: number;
}

interface TelegramConnectProps {
  currentUser: User;
}

const TelegramConnect = ({ currentUser }: TelegramConnectProps) => {
  const [isConnected] = useState(!!currentUser.telegramChatId);
  const botUsername = TELEGRAM_CONFIG.BOT_USERNAME;
  
  const telegramLink = `https://t.me/${botUsername}?start=${encodeURIComponent(currentUser.email)}`;

  return (
    <Card className="border-l-4 border-l-blue-500">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Icon name="Send" size={20} className="text-blue-500" />
          Telegram уведомления
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {isConnected ? (
          <div className="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950/20 rounded-lg">
            <Icon name="CheckCircle2" size={24} className="text-green-600" />
            <div className="flex-1">
              <p className="font-medium text-green-700 dark:text-green-400">
                Telegram подключен
              </p>
              <p className="text-sm text-muted-foreground">
                Вы будете получать уведомления о новых задачах
              </p>
            </div>
            <Badge variant="outline" className="text-green-600 border-green-600">
              Активно
            </Badge>
          </div>
        ) : (
          <div className="space-y-3">
            <div className="p-3 bg-muted/50 rounded-lg">
              <p className="text-sm text-muted-foreground mb-3">
                Подключите Telegram бот для получения уведомлений о новых задачах прямо в мессенджер
              </p>
              <div className="space-y-2 text-sm">
                <p className="flex items-start gap-2">
                  <span className="text-primary font-bold">1.</span>
                  <span>Нажмите кнопку "Подключить Telegram"</span>
                </p>
                <p className="flex items-start gap-2">
                  <span className="text-primary font-bold">2.</span>
                  <span>Нажмите "Start" в боте</span>
                </p>
                <p className="flex items-start gap-2">
                  <span className="text-primary font-bold">3.</span>
                  <span>Готово! Уведомления настроены</span>
                </p>
              </div>
            </div>
            
            <Button 
              onClick={() => window.open(telegramLink, '_blank')}
              className="w-full"
              size="lg"
            >
              <Icon name="Send" size={18} />
              Подключить Telegram
            </Button>
          </div>
        )}

        <div className="pt-3 border-t border-border">
          <p className="text-xs text-muted-foreground">
            <Icon name="Info" size={14} className="inline mr-1" />
            Вы сможете отмечать задачи выполненными прямо в Telegram
          </p>
        </div>
      </CardContent>
    </Card>
  );
};

export default TelegramConnect;