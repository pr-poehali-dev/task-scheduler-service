# Полная инструкция по настройке TaskFlow с Telegram ботом на вашем хостинге

## 📋 Содержание
1. [Подготовка](#1-подготовка)
2. [Создание Telegram бота](#2-создание-telegram-бота)
3. [Настройка базы данных](#3-настройка-базы-данных)
4. [Настройка backend функций](#4-настройка-backend-функций)
5. [Настройка frontend](#5-настройка-frontend)
6. [Настройка webhook](#6-настройка-webhook)
7. [Тестирование](#7-тестирование)
8. [Решение проблем](#8-решение-проблем)

---

## 1. Подготовка

### Что вам понадобится:
- ✅ Хостинг с поддержкой Node.js/Python (VPS, shared hosting)
- ✅ PostgreSQL база данных
- ✅ SSL сертификат (HTTPS обязателен для Telegram webhook)
- ✅ Доступ к Telegram для создания бота
- ✅ Доменное имя (для webhook URL)

### Системные требования:
```
Node.js: 18+ или 22+
Python: 3.11+
PostgreSQL: 12+
```

---

## 2. Создание Telegram бота

### Шаг 1: Создайте бота через @BotFather

1. Откройте Telegram и найдите **@BotFather**
2. Отправьте команду: `/newbot`
3. Придумайте имя бота (например: **TaskFlow Notify Bot**)
4. Придумайте username бота (должен заканчиваться на `bot`, например: **taskflow_notify_bot**)

### Шаг 2: Сохраните токен

После создания бота @BotFather выдаст токен вида:
```
1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
```

**⚠️ ВАЖНО:** Сохраните этот токен — он понадобится на следующих шагах!

### Шаг 3: Настройте бота (опционально)

Можете настроить описание и аватар:
```
/setdescription - описание бота
/setabouttext - информация о боте
/setuserpic - загрузить аватар
```

---

## 3. Настройка базы данных

### Шаг 1: Создайте базу данных PostgreSQL

На вашем хостинге создайте новую базу данных:
```sql
CREATE DATABASE taskflow_db;
```

### Шаг 2: Получите строку подключения (DSN)

Строка подключения имеет формат:
```
postgresql://username:password@host:port/database
```

Пример:
```
postgresql://taskflow_user:mypassword123@localhost:5432/taskflow_db
```

### Шаг 3: Примените миграции

Перейдите в директорию проекта и выполните миграцию из файла `db_migrations/V0001__create_initial_schema.sql`:

```bash
psql -h your_host -U your_username -d taskflow_db -f db_migrations/V0001__create_initial_schema.sql
```

Или подключитесь к базе и выполните SQL напрямую:
```sql
-- Создание таблиц
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    telegram_chat_id BIGINT UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    priority VARCHAR(50) DEFAULT 'medium',
    urgent BOOLEAN DEFAULT FALSE,
    deadline VARCHAR(100),
    completed BOOLEAN DEFAULT FALSE,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS task_assignments (
    id SERIAL PRIMARY KEY,
    task_id INTEGER REFERENCES tasks(id) ON DELETE CASCADE,
    user_email VARCHAR(255) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создание индексов
CREATE INDEX IF NOT EXISTS idx_users_telegram ON users(telegram_chat_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_tasks_completed ON tasks(completed);
CREATE INDEX IF NOT EXISTS idx_task_assignments_task ON task_assignments(task_id);
CREATE INDEX IF NOT EXISTS idx_task_assignments_user ON task_assignments(user_email);
```

---

## 4. Настройка backend функций

### Структура backend функций:
```
/backend/
├── telegram-bot/       # Webhook обработчик Telegram
├── notify-task/        # Отправка уведомлений
├── sync-task/          # Синхронизация статусов
└── save-task/          # Сохранение задач
```

### Шаг 1: Установите зависимости

Для каждой функции установите зависимости:

**Python функции** (telegram-bot, notify-task, sync-task, save-task):
```bash
cd backend/telegram-bot
pip install -r requirements.txt

cd ../notify-task
pip install -r requirements.txt

cd ../sync-task
pip install -r requirements.txt

cd ../save-task
pip install -r requirements.txt
```

### Шаг 2: Настройте переменные окружения

Создайте файл `.env` в корне проекта или настройте переменные на хостинге:

```bash
# Telegram
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz

# Database
DATABASE_URL=postgresql://username:password@host:port/taskflow_db

# Опционально: для логирования
LOG_LEVEL=info
```

### Шаг 3: Деплой функций

#### Вариант A: Serverless (Cloud Functions)

Если ваш хостинг поддерживает serverless функции (AWS Lambda, Yandex Cloud Functions, Google Cloud Functions):

1. Разверните каждую функцию как отдельный endpoint
2. Получите публичные URL для каждой функции
3. Обновите `src/config/backend.ts` с новыми URL:

```typescript
export const BACKEND_URLS = {
  TELEGRAM_BOT: 'https://your-domain.com/api/telegram-bot',
  NOTIFY_TASK: 'https://your-domain.com/api/notify-task',
  SAVE_TASK: 'https://your-domain.com/api/save-task',
  SYNC_TASK: 'https://your-domain.com/api/sync-task'
} as const;
```

#### Вариант B: Traditional Server (VPS)

Если используете обычный сервер:

1. **Используйте API Gateway** (например, Express.js):

```javascript
// server.js
const express = require('express');
const app = express();

// Import Python handlers via child_process or use Node.js handlers
app.post('/api/telegram-bot', async (req, res) => {
  // Call Python function
  const result = await callPythonFunction('telegram-bot', req.body);
  res.json(result);
});

app.post('/api/notify-task', async (req, res) => {
  const result = await callPythonFunction('notify-task', req.body);
  res.json(result);
});

// ... другие endpoints

app.listen(3000, () => {
  console.log('Backend running on port 3000');
});
```

2. **Или используйте Nginx как reverse proxy**:

```nginx
# /etc/nginx/sites-available/taskflow
server {
    listen 443 ssl;
    server_name your-domain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location /api/telegram-bot {
        proxy_pass http://localhost:8001;
    }

    location /api/notify-task {
        proxy_pass http://localhost:8002;
    }

    location /api/sync-task {
        proxy_pass http://localhost:8003;
    }

    location /api/save-task {
        proxy_pass http://localhost:8004;
    }
}
```

---

## 5. Настройка frontend

### Шаг 1: Обновите конфигурацию

Отредактируйте `src/config/backend.ts`:
```typescript
export const BACKEND_URLS = {
  TELEGRAM_BOT: 'https://your-domain.com/api/telegram-bot',
  NOTIFY_TASK: 'https://your-domain.com/api/notify-task',
  SAVE_TASK: 'https://your-domain.com/api/save-task',
  SYNC_TASK: 'https://your-domain.com/api/sync-task'
} as const;
```

Отредактируйте `src/config/telegram.ts`:
```typescript
export const TELEGRAM_CONFIG = {
  BOT_USERNAME: 'taskflow_notify_bot'  // ваш username бота
} as const;
```

### Шаг 2: Соберите проект

```bash
npm install
npm run build
```

### Шаг 3: Деплой на хостинг

Загрузите содержимое папки `dist/` на ваш хостинг в публичную директорию (обычно `public_html`, `www` или `htdocs`).

---

## 6. Настройка webhook

### Способ 1: Через веб-интерфейс (рекомендуется)

1. Откройте ваш TaskFlow в браузере
2. Войдите как администратор
3. Перейдите в раздел **"Настройки"** в боковом меню
4. Вставьте токен бота в поле **"Токен бота"**
5. Нажмите кнопку **"Настроить Webhook"**
6. Дождитесь сообщения об успехе

### Способ 2: Через curl (ручная настройка)

Выполните команду в терминале:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-domain.com/api/telegram-bot",
    "allowed_updates": ["message", "callback_query"]
  }'
```

Замените:
- `<YOUR_BOT_TOKEN>` — на ваш токен бота
- `https://your-domain.com/api/telegram-bot` — на URL вашей функции telegram-bot

### Проверка webhook:

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

Ожидаемый ответ:
```json
{
  "ok": true,
  "result": {
    "url": "https://your-domain.com/api/telegram-bot",
    "has_custom_certificate": false,
    "pending_update_count": 0
  }
}
```

---

## 7. Тестирование

### Тест 1: Проверка подключения бота

1. Откройте Telegram
2. Найдите вашего бота (например: `@taskflow_notify_bot`)
3. Отправьте команду `/start your@email.com`
4. Должно прийти сообщение: "Вы успешно подключены к TaskFlow!"

### Тест 2: Создание задачи

1. Войдите в TaskFlow как администратор
2. Перейдите в раздел **"Команда"**
3. Создайте новую задачу:
   - Название: "Тестовая задача"
   - Назначьте на пользователя, который подключил Telegram
   - Срок: завтра
   - Приоритет: высокий
4. Нажмите "Создать задачу"

### Тест 3: Проверка уведомления

В Telegram должно прийти уведомление:
```
🔥 Новая задача для выполнения

📋 Название: Тестовая задача
📅 Срок: 2025-01-19
📊 Статус: В работе
👤 От кого: Алексей Иванов

[✅ Отметить выполненной]
```

### Тест 4: Отметка выполнения

1. В Telegram нажмите кнопку **"✅ Отметить выполненной"**
2. Задача должна зачеркнуться в Telegram
3. В веб-интерфейсе TaskFlow статус задачи должен измениться на "Выполнено"

---

## 8. Решение проблем

### Проблема: Webhook не настраивается

**Причина:** Нет HTTPS или неверный SSL сертификат

**Решение:**
1. Убедитесь, что ваш сайт работает по HTTPS
2. Проверьте валидность SSL сертификата: https://www.ssllabs.com/ssltest/
3. Telegram требует валидный SSL (не self-signed)

---

### Проблема: Бот не отвечает на команды

**Причина:** Webhook не получает обновления

**Решение:**
1. Проверьте webhook:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

2. Проверьте логи backend функции `telegram-bot`

3. Убедитесь, что URL доступен:
```bash
curl -X POST "https://your-domain.com/api/telegram-bot" \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

---

### Проблема: Уведомления не приходят

**Причина:** Пользователь не подключил Telegram или неверный email

**Решение:**
1. Проверьте, что пользователь выполнил `/start email@example.com` с правильным email
2. Проверьте базу данных:
```sql
SELECT id, name, email, telegram_chat_id FROM users;
```
3. `telegram_chat_id` должен быть заполнен для пользователя

---

### Проблема: Database connection failed

**Причина:** Неверная строка подключения или база недоступна

**Решение:**
1. Проверьте переменную `DATABASE_URL`:
```bash
echo $DATABASE_URL
```

2. Проверьте подключение вручную:
```bash
psql "postgresql://user:pass@host:port/db"
```

3. Убедитесь, что PostgreSQL разрешает удаленные подключения (если база на другом сервере)

---

### Проблема: CORS errors в браузере

**Причина:** Backend функции не возвращают правильные CORS заголовки

**Решение:**
Убедитесь, что все backend функции возвращают:
```python
'Access-Control-Allow-Origin': '*',
'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
'Access-Control-Allow-Headers': 'Content-Type, X-User-Id',
```

---

### Проблема: Задачи не синхронизируются

**Причина:** Frontend не может достучаться до backend

**Решение:**
1. Откройте DevTools (F12) → вкладка Console
2. Проверьте ошибки сети (Network tab)
3. Убедитесь, что URL в `src/config/backend.ts` правильные
4. Проверьте, что backend функции отвечают:
```bash
curl "https://your-domain.com/api/sync-task"
```

---

## 📞 Поддержка

Если у вас возникли проблемы:
1. Проверьте логи backend функций
2. Проверьте логи Telegram webhook: `/getWebhookInfo`
3. Проверьте логи базы данных PostgreSQL
4. Обратитесь в сообщество: https://t.me/+QgiLIa1gFRY4Y2Iy

---

## ✅ Чеклист готовности

- [ ] Бот создан в @BotFather
- [ ] Токен бота сохранен
- [ ] База данных PostgreSQL настроена
- [ ] Миграции применены
- [ ] Backend функции развернуты
- [ ] Frontend собран и загружен на хостинг
- [ ] SSL сертификат установлен (HTTPS работает)
- [ ] Webhook настроен
- [ ] Тестовая задача создана
- [ ] Уведомление получено в Telegram
- [ ] Отметка выполнения работает

**Поздравляем! 🎉 Ваш TaskFlow с Telegram ботом полностью настроен!**
