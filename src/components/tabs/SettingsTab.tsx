import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/ui/icon';
import { BACKEND_URLS } from '@/config/backend';
import { TELEGRAM_CONFIG } from '@/config/telegram';

const SettingsTab = () => {
  const [botToken, setBotToken] = useState('');
  const [botUsername, setBotUsername] = useState(TELEGRAM_CONFIG.BOT_USERNAME);
  const [isSettingWebhook, setIsSettingWebhook] = useState(false);
  const [webhookStatus, setWebhookStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const [webhookMessage, setWebhookMessage] = useState('');

  const handleSetWebhook = async () => {
    if (!botToken.trim()) {
      setWebhookStatus('error');
      setWebhookMessage('Введите токен бота');
      return;
    }

    setIsSettingWebhook(true);
    setWebhookStatus('idle');
    setWebhookMessage('');

    try {
      const webhookUrl = BACKEND_URLS.TELEGRAM_BOT;
      const telegramApiUrl = `https://api.telegram.org/bot${botToken}/setWebhook`;
      
      const response = await fetch(telegramApiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          url: webhookUrl,
          allowed_updates: ['message', 'callback_query']
        })
      });

      const data = await response.json();

      if (data.ok) {
        setWebhookStatus('success');
        setWebhookMessage(`Webhook успешно настроен на ${webhookUrl}`);
      } else {
        setWebhookStatus('error');
        setWebhookMessage(`Ошибка: ${data.description || 'Неизвестная ошибка'}`);
      }
    } catch (error) {
      setWebhookStatus('error');
      setWebhookMessage(`Ошибка подключения: ${error instanceof Error ? error.message : 'Неизвестная ошибка'}`);
    } finally {
      setIsSettingWebhook(false);
    }
  };

  const handleCheckWebhook = async () => {
    if (!botToken.trim()) {
      setWebhookStatus('error');
      setWebhookMessage('Введите токен бота');
      return;
    }

    setIsSettingWebhook(true);
    setWebhookStatus('idle');

    try {
      const telegramApiUrl = `https://api.telegram.org/bot${botToken}/getWebhookInfo`;
      
      const response = await fetch(telegramApiUrl);
      const data = await response.json();

      if (data.ok) {
        const info = data.result;
        if (info.url) {
          setWebhookStatus('success');
          setWebhookMessage(
            `Webhook активен: ${info.url}\n` +
            `Обработано сообщений: ${info.pending_update_count || 0}\n` +
            `${info.last_error_date ? `Последняя ошибка: ${info.last_error_message}` : 'Ошибок нет'}`
          );
        } else {
          setWebhookStatus('error');
          setWebhookMessage('Webhook не настроен');
        }
      } else {
        setWebhookStatus('error');
        setWebhookMessage('Ошибка проверки webhook');
      }
    } catch (error) {
      setWebhookStatus('error');
      setWebhookMessage(`Ошибка: ${error instanceof Error ? error.message : 'Неизвестная ошибка'}`);
    } finally {
      setIsSettingWebhook(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-3xl font-bold tracking-tight mb-2">Настройки</h2>
        <p className="text-muted-foreground">Конфигурация Telegram бота и webhook</p>
      </div>

      <Card className="border-l-4 border-l-blue-500">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Icon name="Settings" size={20} className="text-blue-500" />
            Настройка Telegram бота
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="space-y-4">
            <div>
              <Label htmlFor="bot-token">Токен бота</Label>
              <Input
                id="bot-token"
                type="password"
                value={botToken}
                onChange={(e) => setBotToken(e.target.value)}
                placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                className="mt-1 font-mono"
              />
              <p className="text-xs text-muted-foreground mt-1">
                Получите токен у @BotFather в Telegram
              </p>
            </div>

            <div>
              <Label htmlFor="bot-username">Username бота</Label>
              <Input
                id="bot-username"
                value={botUsername}
                onChange={(e) => setBotUsername(e.target.value)}
                placeholder="taskflow_notify_bot"
                className="mt-1"
              />
              <p className="text-xs text-muted-foreground mt-1">
                Имя бота без @
              </p>
            </div>
          </div>

          <div className="flex gap-3">
            <Button 
              onClick={handleSetWebhook}
              disabled={isSettingWebhook || !botToken.trim()}
              className="flex-1"
            >
              <Icon name={isSettingWebhook ? "Loader2" : "Link"} size={18} className={isSettingWebhook ? "animate-spin" : ""} />
              {isSettingWebhook ? 'Настройка...' : 'Настроить Webhook'}
            </Button>
            <Button 
              onClick={handleCheckWebhook}
              disabled={isSettingWebhook || !botToken.trim()}
              variant="outline"
              className="flex-1"
            >
              <Icon name="Search" size={18} />
              Проверить статус
            </Button>
          </div>

          {webhookStatus !== 'idle' && (
            <div className={`p-4 rounded-lg ${
              webhookStatus === 'success' 
                ? 'bg-green-50 dark:bg-green-950/20' 
                : 'bg-red-50 dark:bg-red-950/20'
            }`}>
              <div className="flex items-start gap-3">
                <Icon 
                  name={webhookStatus === 'success' ? "CheckCircle2" : "AlertCircle"} 
                  size={20} 
                  className={webhookStatus === 'success' ? 'text-green-600' : 'text-red-600'}
                />
                <div className="flex-1">
                  <p className={`font-medium ${
                    webhookStatus === 'success' 
                      ? 'text-green-700 dark:text-green-400' 
                      : 'text-red-700 dark:text-red-400'
                  }`}>
                    {webhookStatus === 'success' ? 'Успешно' : 'Ошибка'}
                  </p>
                  <p className="text-sm text-muted-foreground whitespace-pre-line mt-1">
                    {webhookMessage}
                  </p>
                </div>
              </div>
            </div>
          )}

          <div className="pt-4 border-t border-border">
            <div className="space-y-3">
              <div className="flex items-center gap-2">
                <Badge variant="outline">Webhook URL</Badge>
                <code className="text-xs bg-muted px-2 py-1 rounded">
                  {BACKEND_URLS.TELEGRAM_BOT}
                </code>
              </div>
              <p className="text-xs text-muted-foreground">
                <Icon name="Info" size={14} className="inline mr-1" />
                Этот URL будет использован для получения обновлений от Telegram
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="border-l-4 border-l-purple-500">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Icon name="BookOpen" size={20} className="text-purple-500" />
            Инструкция по настройке
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-start gap-3">
              <Badge className="mt-1">1</Badge>
              <div>
                <p className="font-medium">Создайте бота в Telegram</p>
                <p className="text-sm text-muted-foreground">
                  Откройте @BotFather, отправьте <code className="bg-muted px-1 rounded">/newbot</code>, 
                  придумайте имя и username (например: TaskFlow Notify Bot, taskflow_notify_bot)
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <Badge className="mt-1">2</Badge>
              <div>
                <p className="font-medium">Скопируйте токен</p>
                <p className="text-sm text-muted-foreground">
                  @BotFather выдаст токен вида <code className="bg-muted px-1 rounded">1234567890:ABCdef...</code>. 
                  Вставьте его в поле "Токен бота" выше
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <Badge className="mt-1">3</Badge>
              <div>
                <p className="font-medium">Настройте webhook</p>
                <p className="text-sm text-muted-foreground">
                  Нажмите кнопку "Настроить Webhook" - система автоматически свяжет бота с вашим сервером
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <Badge className="mt-1">4</Badge>
              <div>
                <p className="font-medium">Добавьте токен в секреты</p>
                <p className="text-sm text-muted-foreground">
                  В настройках проекта добавьте секрет <code className="bg-muted px-1 rounded">TELEGRAM_BOT_TOKEN</code> 
                  со значением вашего токена
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <Badge className="mt-1">5</Badge>
              <div>
                <p className="font-medium">Готово!</p>
                <p className="text-sm text-muted-foreground">
                  Пользователи могут подключиться к боту через раздел "Профиль"
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default SettingsTab;
