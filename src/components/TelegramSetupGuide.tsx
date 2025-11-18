import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';

const TelegramSetupGuide = () => {
  return (
    <Card className="border-l-4 border-l-purple-500">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Icon name="Info" size={20} className="text-purple-500" />
          Как работают уведомления в Telegram
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-3">
          <div className="flex items-start gap-3">
            <Badge className="mt-1">1</Badge>
            <div>
              <p className="font-medium">Подключите бота</p>
              <p className="text-sm text-muted-foreground">
                Нажмите кнопку "Подключить Telegram" в профиле
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3">
            <Badge className="mt-1">2</Badge>
            <div>
              <p className="font-medium">Получайте уведомления</p>
              <p className="text-sm text-muted-foreground">
                Когда вам назначат задачу, придёт сообщение в Telegram с деталями
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3">
            <Badge className="mt-1">3</Badge>
            <div>
              <p className="font-medium">Выполняйте задачи</p>
              <p className="text-sm text-muted-foreground">
                Нажмите "Отметить выполненной" прямо в Telegram - статус обновится везде
              </p>
            </div>
          </div>
        </div>

        <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
          <p className="text-sm text-blue-700 dark:text-blue-400 flex items-start gap-2">
            <Icon name="Sparkles" size={16} className="mt-0.5" />
            <span>
              Вам придёт уведомление с названием задачи, сроком выполнения и информацией о том, кто её создал
            </span>
          </p>
        </div>
      </CardContent>
    </Card>
  );
};

export default TelegramSetupGuide;
