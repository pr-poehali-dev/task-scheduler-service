# 📁 Установка TaskFlow через файловый менеджер хостинга

## Для кого эта инструкция?

Если у вас:
- ✅ Обычный хостинг (shared hosting) с панелью управления
- ✅ Доступ к файловому менеджеру (cPanel, ISPmanager, Plesk, DirectAdmin)
- ✅ Поддержка Python 3.11+ и PostgreSQL
- ✅ Нет доступа к SSH или Docker

То эта инструкция для вас!

---

## 📋 Что понадобится

1. **Хостинг с поддержкой:**
   - Python 3.11 или выше
   - PostgreSQL 12 или выше
   - SSL сертификат (HTTPS)
   - CGI/WSGI для запуска Python скриптов

2. **Токен Telegram бота** (получите у @BotFather)

3. **15-30 минут времени**

---

## 🎯 Общая схема

```
Ваш хостинг:
├── public_html/                  ← Frontend (React)
│   ├── index.html
│   ├── assets/
│   └── .htaccess
└── cgi-bin/                      ← Backend (Python)
    ├── telegram-bot.py
    ├── notify-task.py
    ├── sync-task.py
    └── save-task.py
```

---

## Шаг 1: Подготовка файлов локально

### 1.1. Скачайте проект

Скачайте все файлы проекта к себе на компьютер или клонируйте через Git:

```bash
git clone your-repo-url taskflow
cd taskflow
```

### 1.2. Соберите frontend

```bash
# Установите зависимости
npm install

# Обновите backend URLs в src/config/backend.ts
# Замените на URL вашего хостинга:

export const BACKEND_URLS = {
  TELEGRAM_BOT: 'https://your-domain.com/cgi-bin/telegram-bot.py',
  NOTIFY_TASK: 'https://your-domain.com/cgi-bin/notify-task.py',
  SAVE_TASK: 'https://your-domain.com/cgi-bin/save-task.py',
  SYNC_TASK: 'https://your-domain.com/cgi-bin/sync-task.py'
} as const;

# Соберите проект
npm run build
```

Готово! Теперь у вас есть папка `dist/` с готовым frontend.

### 1.3. Подготовьте CGI обертки для backend

Создайте файлы CGI оберток на вашем компьютере. Ниже примеры для каждой функции.

---

## Шаг 2: Создание CGI оберток

### Файл: `telegram-bot.py`

Создайте файл `telegram-bot.py` на компьютере:

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
import json
import cgitb

# Включаем отладку CGI (закомментируйте в production)
cgitb.enable()

# Добавляем путь к модулям backend
sys.path.insert(0, '/home/your_username/backend')
sys.path.insert(0, '/home/your_username/backend/telegram-bot')

# Импортируем handler из модуля
try:
    from backend.telegram_bot.index import handler
except ImportError:
    # Альтернативный импорт
    import importlib.util
    spec = importlib.util.spec_from_file_location(
        "telegram_bot", 
        "/home/your_username/backend/telegram-bot/index.py"
    )
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    handler = module.handler

# Получаем данные из CGI
def get_cgi_data():
    method = os.environ.get('REQUEST_METHOD', 'POST')
    
    # Читаем body
    content_length = int(os.environ.get('CONTENT_LENGTH', 0))
    body = sys.stdin.read(content_length) if content_length > 0 else ''
    
    # Собираем headers
    headers = {}
    for key, value in os.environ.items():
        if key.startswith('HTTP_'):
            header_name = key[5:].replace('_', '-').lower()
            headers[header_name] = value
    
    # Создаем event в формате Cloud Function
    event = {
        'httpMethod': method,
        'headers': headers,
        'body': body,
        'queryStringParameters': {},
        'isBase64Encoded': False,
        'requestContext': {
            'requestId': os.environ.get('UNIQUE_ID', 'cgi-request'),
            'identity': {
                'sourceIp': os.environ.get('REMOTE_ADDR', 'unknown'),
                'userAgent': os.environ.get('HTTP_USER_AGENT', '')
            },
            'httpMethod': method
        }
    }
    
    return event

# Mock context
class Context:
    def __init__(self):
        self.request_id = os.environ.get('UNIQUE_ID', 'cgi-request')
        self.function_name = 'telegram-bot'
        self.function_version = '1.0.0'
        self.memory_limit_in_mb = 256

# Основная логика
try:
    # Получаем event
    event = get_cgi_data()
    context = Context()
    
    # Вызываем handler
    result = handler(event, context)
    
    # Выводим результат
    print("Content-Type: application/json")
    print(f"Status: {result.get('statusCode', 200)}")
    
    # Дополнительные заголовки
    for key, value in result.get('headers', {}).items():
        print(f"{key}: {value}")
    
    print()  # Пустая строка между headers и body
    print(result.get('body', '{}'))
    
except Exception as e:
    # Обработка ошибок
    print("Content-Type: application/json")
    print("Status: 500")
    print()
    print(json.dumps({
        'error': 'Internal Server Error',
        'message': str(e)
    }))
```

### Файл: `notify-task.py`

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
import json
import cgitb

cgitb.enable()

sys.path.insert(0, '/home/your_username/backend')
sys.path.insert(0, '/home/your_username/backend/notify-task')

# Импорт handler
import importlib.util
spec = importlib.util.spec_from_file_location(
    "notify_task", 
    "/home/your_username/backend/notify-task/index.py"
)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
handler = module.handler

def get_cgi_data():
    method = os.environ.get('REQUEST_METHOD', 'POST')
    content_length = int(os.environ.get('CONTENT_LENGTH', 0))
    body = sys.stdin.read(content_length) if content_length > 0 else ''
    
    headers = {}
    for key, value in os.environ.items():
        if key.startswith('HTTP_'):
            header_name = key[5:].replace('_', '-').lower()
            headers[header_name] = value
    
    return {
        'httpMethod': method,
        'headers': headers,
        'body': body,
        'queryStringParameters': {},
        'isBase64Encoded': False,
        'requestContext': {
            'requestId': os.environ.get('UNIQUE_ID', 'cgi-request'),
            'identity': {
                'sourceIp': os.environ.get('REMOTE_ADDR', 'unknown'),
                'userAgent': os.environ.get('HTTP_USER_AGENT', '')
            }
        }
    }

class Context:
    request_id = os.environ.get('UNIQUE_ID', 'cgi-request')
    function_name = 'notify-task'

try:
    event = get_cgi_data()
    context = Context()
    result = handler(event, context)
    
    print("Content-Type: application/json")
    print(f"Status: {result.get('statusCode', 200)}")
    for key, value in result.get('headers', {}).items():
        print(f"{key}: {value}")
    print()
    print(result.get('body', '{}'))
except Exception as e:
    print("Content-Type: application/json")
    print("Status: 500")
    print()
    print(json.dumps({'error': str(e)}))
```

### Файл: `sync-task.py`

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
import json
import cgitb

cgitb.enable()

sys.path.insert(0, '/home/your_username/backend')
sys.path.insert(0, '/home/your_username/backend/sync-task')

import importlib.util
spec = importlib.util.spec_from_file_location(
    "sync_task", 
    "/home/your_username/backend/sync-task/index.py"
)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
handler = module.handler

def get_cgi_data():
    method = os.environ.get('REQUEST_METHOD', 'POST')
    content_length = int(os.environ.get('CONTENT_LENGTH', 0))
    body = sys.stdin.read(content_length) if content_length > 0 else ''
    
    headers = {}
    for key, value in os.environ.items():
        if key.startswith('HTTP_'):
            header_name = key[5:].replace('_', '-').lower()
            headers[header_name] = value
    
    return {
        'httpMethod': method,
        'headers': headers,
        'body': body,
        'queryStringParameters': {},
        'isBase64Encoded': False,
        'requestContext': {
            'requestId': os.environ.get('UNIQUE_ID', 'cgi-request'),
            'identity': {
                'sourceIp': os.environ.get('REMOTE_ADDR', 'unknown'),
                'userAgent': os.environ.get('HTTP_USER_AGENT', '')
            }
        }
    }

class Context:
    request_id = os.environ.get('UNIQUE_ID', 'cgi-request')
    function_name = 'sync-task'

try:
    event = get_cgi_data()
    context = Context()
    result = handler(event, context)
    
    print("Content-Type: application/json")
    print(f"Status: {result.get('statusCode', 200)}")
    for key, value in result.get('headers', {}).items():
        print(f"{key}: {value}")
    print()
    print(result.get('body', '{}'))
except Exception as e:
    print("Content-Type: application/json")
    print("Status: 500")
    print()
    print(json.dumps({'error': str(e)}))
```

### Файл: `save-task.py`

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
import json
import cgitb

cgitb.enable()

sys.path.insert(0, '/home/your_username/backend')
sys.path.insert(0, '/home/your_username/backend/save-task')

import importlib.util
spec = importlib.util.spec_from_file_location(
    "save_task", 
    "/home/your_username/backend/save-task/index.py"
)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
handler = module.handler

def get_cgi_data():
    method = os.environ.get('REQUEST_METHOD', 'POST')
    content_length = int(os.environ.get('CONTENT_LENGTH', 0))
    body = sys.stdin.read(content_length) if content_length > 0 else ''
    
    headers = {}
    for key, value in os.environ.items():
        if key.startswith('HTTP_'):
            header_name = key[5:].replace('_', '-').lower()
            headers[header_name] = value
    
    return {
        'httpMethod': method,
        'headers': headers,
        'body': body,
        'queryStringParameters': {},
        'isBase64Encoded': False,
        'requestContext': {
            'requestId': os.environ.get('UNIQUE_ID', 'cgi-request'),
            'identity': {
                'sourceIp': os.environ.get('REMOTE_ADDR', 'unknown'),
                'userAgent': os.environ.get('HTTP_USER_AGENT', '')
            }
        }
    }

class Context:
    request_id = os.environ.get('UNIQUE_ID', 'cgi-request')
    function_name = 'save-task'

try:
    event = get_cgi_data()
    context = Context()
    result = handler(event, context)
    
    print("Content-Type: application/json")
    print(f"Status: {result.get('statusCode', 200)}")
    for key, value in result.get('headers', {}).items():
        print(f"{key}: {value}")
    print()
    print(result.get('body', '{}'))
except Exception as e:
    print("Content-Type: application/json")
    print("Status: 500")
    print()
    print(json.dumps({'error': str(e)}))
```

**⚠️ ВАЖНО:** Замените `/home/your_username/` на реальный путь на вашем хостинге!

---

## Шаг 3: Создание .htaccess

Создайте файл `.htaccess` для правильной маршрутизации:

```apache
# .htaccess для public_html/

# Включаем mod_rewrite
RewriteEngine On

# CORS заголовки для API
<FilesMatch "\.(py)$">
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, X-User-Id, X-Auth-Token"
</FilesMatch>

# Обработка OPTIONS запросов
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Frontend SPA - если файл не существует, отдаем index.html
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/cgi-bin/
RewriteRule ^ /index.html [L]

# Отключаем листинг директорий
Options -Indexes

# Защита .env и конфиг файлов
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## Шаг 4: Загрузка файлов через файловый менеджер

### 4.1. Войдите в панель управления хостингом

Откройте cPanel, ISPmanager, Plesk или любую другую панель вашего хостинга.

### 4.2. Откройте файловый менеджер

Найдите раздел "Файловый менеджер" или "File Manager".

### 4.3. Создайте структуру директорий

Создайте следующие папки (если их нет):

```
/home/your_username/
├── public_html/          ← Веб-сайт (Frontend)
├── cgi-bin/              ← Python скрипты (Backend)
├── backend/              ← Модули backend функций
│   ├── telegram-bot/
│   ├── notify-task/
│   ├── sync-task/
│   └── save-task/
└── .env                  ← Переменные окружения
```

### 4.4. Загрузите Frontend

1. Откройте папку `public_html/`
2. **Удалите** все существующие файлы (если есть)
3. **Загрузите** все файлы из папки `dist/`:
   - `index.html`
   - `assets/` (вся папка)
   - `vite.svg` (если есть)
4. **Загрузите** файл `.htaccess` (созданный в Шаге 3)

### 4.5. Загрузите Backend

1. Откройте папку `cgi-bin/`
2. **Загрузите** все 4 CGI обертки:
   - `telegram-bot.py`
   - `notify-task.py`
   - `sync-task.py`
   - `save-task.py`

3. Откройте папку `/home/your_username/backend/`
4. **Загрузите** все папки с backend функциями:
   - `telegram-bot/` (со всеми файлами внутри)
   - `notify-task/`
   - `sync-task/`
   - `save-task/`

### 4.6. Установите права доступа

**Важно!** Установите правильные права:

1. Для CGI скриптов (`cgi-bin/*.py`):
   - Права: **755** (rwxr-xr-x)
   - Через файловый менеджер: Выделите файл → Права → 755

2. Для backend модулей (`backend/*/index.py`):
   - Права: **644** (rw-r--r--)

3. Для frontend файлов (`public_html/*`):
   - Файлы: **644**
   - Папки: **755**

---

## Шаг 5: Настройка базы данных PostgreSQL

### 5.1. Создайте базу данных

В панели управления хостингом:

1. Найдите раздел **"PostgreSQL Databases"** или **"Базы данных"**
2. Создайте новую базу:
   - Имя: `taskflow_db`
   - Пользователь: `taskflow_user`
   - Пароль: придумайте сложный пароль
3. Назначьте пользователя к базе данных

### 5.2. Примените миграции

1. Откройте **phpPgAdmin** или **Adminer** (если есть в панели)
2. Выберите базу `taskflow_db`
3. Откройте вкладку **SQL**
4. Скопируйте содержимое файла `db_migrations/V0001__create_initial_schema.sql`
5. Вставьте в окно SQL и нажмите **Execute**

Или через файловый менеджер:
1. Загрузите `V0001__create_initial_schema.sql` в любую папку
2. Через SSH (если доступен) выполните:
```bash
psql -h localhost -U taskflow_user -d taskflow_db -f V0001__create_initial_schema.sql
```

### 5.3. Получите строку подключения

Строка подключения (DSN) выглядит так:
```
postgresql://taskflow_user:your_password@localhost:5432/taskflow_db
```

Или если база на другом хосте:
```
postgresql://taskflow_user:your_password@db.your-host.com:5432/taskflow_db
```

---

## Шаг 6: Настройка переменных окружения

### Вариант A: Через .env файл

Создайте файл `.env` в корневой директории `/home/your_username/.env`:

```bash
# Telegram Bot
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz

# Database
DATABASE_URL=postgresql://taskflow_user:your_password@localhost:5432/taskflow_db

# Environment
PYTHON_ENV=production
```

**Важно:** Убедитесь, что Python скрипты могут читать этот файл.

### Вариант B: Через переменные окружения хостинга

Некоторые хостинги позволяют устанавливать переменные окружения через панель управления:

1. Найдите раздел **"Environment Variables"** или **"Переменные окружения"**
2. Добавьте:
   - `TELEGRAM_BOT_TOKEN` = ваш_токен
   - `DATABASE_URL` = строка_подключения

### Вариант C: Хардкод в CGI скриптах (не рекомендуется)

В каждом CGI скрипте добавьте в начало:

```python
os.environ['TELEGRAM_BOT_TOKEN'] = '1234567890:ABC...'
os.environ['DATABASE_URL'] = 'postgresql://...'
```

---

## Шаг 7: Установка Python зависимостей

### 7.1. Проверьте версию Python

Через SSH (если доступен):
```bash
python3 --version
# Должно быть 3.11 или выше
```

### 7.2. Установите pip (если нет)

```bash
python3 -m ensurepip --upgrade
```

### 7.3. Установите зависимости

Через SSH:
```bash
cd /home/your_username/backend/telegram-bot
pip3 install --user -r requirements.txt

cd /home/your_username/backend/notify-task
pip3 install --user -r requirements.txt

cd /home/your_username/backend/sync-task
pip3 install --user -r requirements.txt

cd /home/your_username/backend/save-task
pip3 install --user -r requirements.txt
```

**Если нет SSH:**
Обратитесь в поддержку хостинга с просьбой установить:
- `psycopg2-binary`
- `pydantic`

Или загрузите библиотеки вручную в папку `backend/` и добавьте в `sys.path`.

---

## Шаг 8: Настройка Telegram webhook

### 8.1. Узнайте URL вашего CGI скрипта

URL будет выглядеть так:
```
https://your-domain.com/cgi-bin/telegram-bot.py
```

### 8.2. Настройте webhook

Откройте в браузере (или через curl):

```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://your-domain.com/cgi-bin/telegram-bot.py
```

Замените:
- `<YOUR_BOT_TOKEN>` на ваш токен
- `your-domain.com` на ваш домен

Ответ должен быть:
```json
{"ok":true,"result":true,"description":"Webhook was set"}
```

### 8.3. Проверьте webhook

```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo
```

Должен вернуть:
```json
{
  "ok": true,
  "result": {
    "url": "https://your-domain.com/cgi-bin/telegram-bot.py",
    "has_custom_certificate": false,
    "pending_update_count": 0
  }
}
```

---

## Шаг 9: Проверка работоспособности

### 9.1. Проверьте Frontend

Откройте в браузере:
```
https://your-domain.com
```

Должна открыться страница входа TaskFlow.

### 9.2. Проверьте Backend API

Откройте в браузере или через curl:

```
https://your-domain.com/cgi-bin/telegram-bot.py
```

Должен вернуть JSON (возможно ошибку, но не 404).

### 9.3. Проверьте Telegram бота

1. Найдите бота в Telegram
2. Отправьте: `/start test@example.com`
3. Должен прийти ответ от бота

### 9.4. Создайте тестовую задачу

1. Войдите в TaskFlow
2. Создайте задачу и назначьте на пользователя
3. Проверьте, пришло ли уведомление в Telegram

---

## Решение проблем

### Проблема: 500 Internal Server Error на CGI скриптах

**Причины:**
1. Неправильные права доступа (должно быть 755)
2. Неверный путь к Python в шебанге (`#!/usr/bin/env python3`)
3. Синтаксическая ошибка в скрипте

**Решение:**
1. Проверьте права: 755 для .py файлов
2. Проверьте путь к Python на хостинге (может быть `/usr/local/bin/python3`)
3. Проверьте логи ошибок (обычно в `error_log` хостинга)

### Проблема: ModuleNotFoundError

**Причина:** Python не находит модули

**Решение:**
1. Убедитесь, что `sys.path.insert()` указывает на правильные пути
2. Замените `/home/your_username/` на реальный путь
3. Установите зависимости: `pip3 install --user -r requirements.txt`

### Проблема: Database connection error

**Причина:** Неверная строка подключения

**Решение:**
1. Проверьте DATABASE_URL в .env
2. Проверьте, что PostgreSQL доступен с хостинга (может требоваться `localhost` или IP)
3. Проверьте логин/пароль от базы данных

### Проблема: CORS errors

**Причина:** Браузер блокирует запросы

**Решение:**
1. Убедитесь, что .htaccess загружен в `public_html/`
2. Добавьте CORS заголовки в CGI скрипты:
```python
print("Access-Control-Allow-Origin: *")
print("Access-Control-Allow-Methods: GET, POST, OPTIONS")
print("Access-Control-Allow-Headers: Content-Type")
```

### Проблема: Telegram webhook не работает

**Причины:**
1. Нет HTTPS (Telegram требует SSL)
2. Webhook URL неверный
3. CGI скрипт возвращает ошибку

**Решение:**
1. Убедитесь, что сайт работает по HTTPS
2. Проверьте URL через `/getWebhookInfo`
3. Проверьте логи CGI скрипта
4. Удалите webhook и установите заново:
```
https://api.telegram.org/bot<TOKEN>/deleteWebhook
https://api.telegram.org/bot<TOKEN>/setWebhook?url=...
```

---

## Альтернативные варианты

### Если хостинг не поддерживает Python CGI:

1. **Используйте Flask/Django + WSGI:**
   - Создайте Flask приложение
   - Настройте WSGI через панель хостинга
   - Все backend функции как Flask routes

2. **Используйте Node.js вместо Python:**
   - Перепишите backend на Node.js
   - Используйте node-postgres для работы с БД

3. **Используйте внешний serverless:**
   - Разместите backend на AWS Lambda / Google Cloud Functions
   - Frontend оставьте на хостинге

---

## Чеклист установки

- [ ] Frontend собран (`npm run build`)
- [ ] Backend URLs обновлены в `src/config/backend.ts`
- [ ] Все файлы загружены через файловый менеджер
- [ ] Права доступа установлены (755 для CGI)
- [ ] База данных PostgreSQL создана
- [ ] Миграции применены
- [ ] Переменные окружения настроены (.env или через панель)
- [ ] Python зависимости установлены
- [ ] .htaccess загружен в public_html/
- [ ] Telegram webhook настроен
- [ ] Frontend открывается в браузере
- [ ] Backend API отвечает
- [ ] Тестовая задача создана
- [ ] Уведомление получено в Telegram

---

## Поддержка

Если что-то не получается:
1. Проверьте логи хостинга (error_log)
2. Включите отладку CGI: `cgitb.enable()` в скриптах
3. Проверьте документацию вашего хостинга по Python CGI
4. Обратитесь в поддержку хостинга
5. Задайте вопрос в сообществе: https://t.me/+QgiLIa1gFRY4Y2Iy

---

**Готово! 🎉**

Ваш TaskFlow установлен через файловый менеджер и готов к работе!
