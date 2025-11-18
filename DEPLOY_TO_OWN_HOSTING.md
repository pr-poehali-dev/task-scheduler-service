# 🚀 Полная инструкция: Развертывание TaskFlow на собственном хостинге

## Содержание
1. [Архитектура проекта](#архитектура-проекта)
2. [Требования к хостингу](#требования-к-хостингу)
3. [Вариант A: VPS с Docker](#вариант-a-vps-с-docker-рекомендуется)
4. [Вариант B: Shared Hosting](#вариант-b-shared-hosting)
5. [Вариант C: Serverless Functions](#вариант-c-serverless-functions)
6. [Настройка базы данных](#настройка-базы-данных)
7. [Настройка Telegram бота](#настройка-telegram-бота)
8. [Проверка работоспособности](#проверка-работоспособности)

---

## Архитектура проекта

```
TaskFlow
├── Frontend (React SPA)          → Статические файлы (HTML/CSS/JS)
├── Backend Functions             → 4 серверные функции
│   ├── telegram-bot (Python)    → Webhook для Telegram
│   ├── notify-task (Python)     → Отправка уведомлений
│   ├── sync-task (Python)       → Синхронизация статусов
│   └── save-task (Python)       → Сохранение задач в БД
└── Database (PostgreSQL)         → Хранение данных
```

---

## Требования к хостингу

### Минимальные требования:
- **ОС:** Linux (Ubuntu 20.04+, Debian 10+, CentOS 7+)
- **CPU:** 1 core
- **RAM:** 1 GB
- **Диск:** 10 GB
- **Python:** 3.11+
- **PostgreSQL:** 12+
- **Node.js:** 18+ (для сборки frontend)
- **SSL:** Обязателен (Let's Encrypt подойдет)

### Рекомендуемые провайдеры:
- **VPS:** DigitalOcean, Hetzner, Timeweb, Selectel
- **Serverless:** AWS Lambda, Google Cloud Functions, Yandex Cloud Functions
- **Shared Hosting:** Beget, Timeweb (если поддерживают Python)

---

## Вариант A: VPS с Docker (рекомендуется)

### Преимущества:
✅ Проще настраивать  
✅ Легко масштабировать  
✅ Изолированное окружение  
✅ Быстрый деплой

### Шаг 1: Подготовка VPS

```bash
# Подключитесь к серверу
ssh root@your-server-ip

# Обновите систему
apt update && apt upgrade -y

# Установите Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Установите Docker Compose
apt install docker-compose -y

# Проверьте установку
docker --version
docker-compose --version
```

### Шаг 2: Создайте структуру проекта

```bash
# Создайте директорию проекта
mkdir -p /opt/taskflow
cd /opt/taskflow

# Клонируйте проект (или загрузите файлы)
git clone your-repo-url .
# ИЛИ загрузите через scp/sftp
```

### Шаг 3: Создайте Docker Compose файл

```bash
nano docker-compose.yml
```

Вставьте содержимое:

```yaml
version: '3.8'

services:
  # PostgreSQL Database
  postgres:
    image: postgres:15-alpine
    container_name: taskflow-db
    environment:
      POSTGRES_DB: taskflow_db
      POSTGRES_USER: taskflow_user
      POSTGRES_PASSWORD: your_strong_password_here
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./db_migrations:/docker-entrypoint-initdb.d
    ports:
      - "5432:5432"
    restart: always
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U taskflow_user"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Backend API Gateway (Python FastAPI)
  backend:
    build:
      context: .
      dockerfile: Dockerfile.backend
    container_name: taskflow-backend
    environment:
      DATABASE_URL: postgresql://taskflow_user:your_strong_password_here@postgres:5432/taskflow_db
      TELEGRAM_BOT_TOKEN: ${TELEGRAM_BOT_TOKEN}
      PYTHON_ENV: production
    ports:
      - "8000:8000"
    depends_on:
      postgres:
        condition: service_healthy
    restart: always
    volumes:
      - ./backend:/app/backend

  # Nginx (Frontend + Reverse Proxy)
  nginx:
    image: nginx:alpine
    container_name: taskflow-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./dist:/usr/share/nginx/html
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - backend
    restart: always

volumes:
  postgres_data:
```

### Шаг 4: Создайте Dockerfile для backend

```bash
nano Dockerfile.backend
```

```dockerfile
FROM python:3.11-slim

WORKDIR /app

# Установка зависимостей системы
RUN apt-get update && apt-get install -y \
    gcc \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Копирование requirements
COPY backend/telegram-bot/requirements.txt /app/requirements-telegram.txt
COPY backend/notify-task/requirements.txt /app/requirements-notify.txt
COPY backend/sync-task/requirements.txt /app/requirements-sync.txt
COPY backend/save-task/requirements.txt /app/requirements-save.txt

# Установка Python зависимостей
RUN pip install --no-cache-dir \
    fastapi uvicorn \
    -r /app/requirements-telegram.txt \
    -r /app/requirements-notify.txt \
    -r /app/requirements-sync.txt \
    -r /app/requirements-save.txt

# Копирование кода
COPY backend /app/backend
COPY server.py /app/server.py

EXPOSE 8000

CMD ["uvicorn", "server:app", "--host", "0.0.0.0", "--port", "8000"]
```

### Шаг 5: Создайте API Gateway (FastAPI)

```bash
nano server.py
```

```python
from fastapi import FastAPI, Request, Response
from fastapi.middleware.cors import CORSMiddleware
import importlib.util
import sys
import json
from typing import Dict, Any

app = FastAPI(title="TaskFlow Backend API")

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Функция для загрузки Python модулей
def load_function_handler(function_name: str):
    """Динамическая загрузка handler функции из модуля"""
    module_path = f"/app/backend/{function_name}/index.py"
    spec = importlib.util.spec_from_file_location(function_name, module_path)
    module = importlib.util.module_from_spec(spec)
    sys.modules[function_name] = module
    spec.loader.exec_module(module)
    return module.handler

# Загрузка всех handlers
telegram_bot_handler = load_function_handler("telegram-bot")
notify_task_handler = load_function_handler("notify-task")
sync_task_handler = load_function_handler("sync-task")
save_task_handler = load_function_handler("save-task")

# Mock context объект
class MockContext:
    def __init__(self):
        self.request_id = "local-request"
        self.function_name = "local-function"
        self.function_version = "1.0.0"
        self.memory_limit_in_mb = 256

async def create_event(request: Request) -> Dict[str, Any]:
    """Преобразование FastAPI Request в Cloud Function Event"""
    body = await request.body()
    
    return {
        "httpMethod": request.method,
        "headers": dict(request.headers),
        "queryStringParameters": dict(request.query_params),
        "body": body.decode() if body else "",
        "isBase64Encoded": False,
        "requestContext": {
            "requestId": request.headers.get("x-request-id", "unknown"),
            "identity": {
                "sourceIp": request.client.host,
                "userAgent": request.headers.get("user-agent", "")
            },
            "httpMethod": request.method
        }
    }

def create_response(result: Dict[str, Any]) -> Response:
    """Преобразование Cloud Function Response в FastAPI Response"""
    return Response(
        content=result.get("body", ""),
        status_code=result.get("statusCode", 200),
        headers=result.get("headers", {}),
        media_type="application/json"
    )

# Endpoints
@app.api_route("/api/telegram-bot", methods=["GET", "POST", "OPTIONS"])
async def telegram_bot(request: Request):
    event = await create_event(request)
    context = MockContext()
    result = telegram_bot_handler(event, context)
    return create_response(result)

@app.api_route("/api/notify-task", methods=["GET", "POST", "OPTIONS"])
async def notify_task(request: Request):
    event = await create_event(request)
    context = MockContext()
    result = notify_task_handler(event, context)
    return create_response(result)

@app.api_route("/api/sync-task", methods=["GET", "POST", "OPTIONS"])
async def sync_task(request: Request):
    event = await create_event(request)
    context = MockContext()
    result = sync_task_handler(event, context)
    return create_response(result)

@app.api_route("/api/save-task", methods=["GET", "POST", "OPTIONS"])
async def save_task(request: Request):
    event = await create_event(request)
    context = MockContext()
    result = save_task_handler(event, context)
    return create_response(result)

@app.get("/health")
async def health():
    return {"status": "ok", "service": "taskflow-backend"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
```

### Шаг 6: Создайте Nginx конфигурацию

```bash
nano nginx.conf
```

```nginx
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Логи
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Upstream для backend
    upstream backend {
        server backend:8000;
    }

    # HTTP (redirect to HTTPS)
    server {
        listen 80;
        server_name your-domain.com www.your-domain.com;

        location /.well-known/acme-challenge/ {
            root /usr/share/nginx/html;
        }

        location / {
            return 301 https://$server_name$request_uri;
        }
    }

    # HTTPS
    server {
        listen 443 ssl http2;
        server_name your-domain.com www.your-domain.com;

        # SSL сертификаты (Let's Encrypt)
        ssl_certificate /etc/nginx/ssl/fullchain.pem;
        ssl_certificate_key /etc/nginx/ssl/privkey.pem;
        
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers HIGH:!aNULL:!MD5;
        ssl_prefer_server_ciphers on;

        # Frontend (React SPA)
        location / {
            root /usr/share/nginx/html;
            try_files $uri $uri/ /index.html;
            
            # Cache для статики
            location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
                expires 1y;
                add_header Cache-Control "public, immutable";
            }
        }

        # Backend API
        location /api/ {
            proxy_pass http://backend;
            proxy_http_version 1.1;
            
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
            
            proxy_connect_timeout 60s;
            proxy_send_timeout 60s;
            proxy_read_timeout 60s;
        }

        # Health check
        location /health {
            proxy_pass http://backend/health;
            access_log off;
        }
    }
}
```

### Шаг 7: Получите SSL сертификат

```bash
# Установите Certbot
apt install certbot python3-certbot-nginx -y

# Получите сертификат
certbot certonly --nginx -d your-domain.com -d www.your-domain.com

# Скопируйте сертификаты
mkdir -p /opt/taskflow/ssl
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem /opt/taskflow/ssl/
cp /etc/letsencrypt/live/your-domain.com/privkey.pem /opt/taskflow/ssl/

# Настройте автообновление
crontab -e
# Добавьте строку:
0 3 * * * certbot renew --quiet && cp /etc/letsencrypt/live/your-domain.com/*.pem /opt/taskflow/ssl/ && docker-compose restart nginx
```

### Шаг 8: Соберите frontend

```bash
# Локально на вашем компьютере
npm install
npm run build

# Загрузите dist/ на сервер
scp -r dist/* root@your-server:/opt/taskflow/dist/
```

### Шаг 9: Обновите конфигурацию frontend

Отредактируйте `src/config/backend.ts`:

```typescript
export const BACKEND_URLS = {
  TELEGRAM_BOT: 'https://your-domain.com/api/telegram-bot',
  NOTIFY_TASK: 'https://your-domain.com/api/notify-task',
  SAVE_TASK: 'https://your-domain.com/api/save-task',
  SYNC_TASK: 'https://your-domain.com/api/sync-task'
} as const;
```

Пересоберите и загрузите снова.

### Шаг 10: Создайте .env файл

```bash
cd /opt/taskflow
nano .env
```

```bash
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
DATABASE_URL=postgresql://taskflow_user:your_strong_password_here@postgres:5432/taskflow_db
```

### Шаг 11: Запустите проект

```bash
cd /opt/taskflow

# Запустите все сервисы
docker-compose up -d

# Проверьте статус
docker-compose ps

# Проверьте логи
docker-compose logs -f backend
```

### Шаг 12: Примените миграции базы данных

```bash
# Подключитесь к контейнеру postgres
docker exec -it taskflow-db psql -U taskflow_user -d taskflow_db

# Или выполните миграцию напрямую
docker exec -i taskflow-db psql -U taskflow_user -d taskflow_db < db_migrations/V0001__create_initial_schema.sql
```

### Шаг 13: Настройте Telegram webhook

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-domain.com/api/telegram-bot",
    "allowed_updates": ["message", "callback_query"]
  }'
```

### Проверка:

```bash
# Проверьте доступность
curl https://your-domain.com/health

# Проверьте webhook
curl "https://api.telegram.org/bot<YOUR_TOKEN>/getWebhookInfo"

# Проверьте frontend
curl -I https://your-domain.com
```

---

## Вариант B: Shared Hosting

### Если ваш хостинг поддерживает Python + PostgreSQL:

1. **Загрузите файлы через FTP/SFTP**
2. **Настройте CGI/WSGI для Python** (зависит от хостинга)
3. **Используйте .htaccess для роутинга**:

```apache
# .htaccess
RewriteEngine On

# Backend API
RewriteRule ^api/telegram-bot$ /cgi-bin/telegram-bot.py [L]
RewriteRule ^api/notify-task$ /cgi-bin/notify-task.py [L]
RewriteRule ^api/sync-task$ /cgi-bin/sync-task.py [L]
RewriteRule ^api/save-task$ /cgi-bin/save-task.py [L]

# Frontend SPA
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
```

4. **Создайте CGI обертки**:

```python
#!/usr/bin/env python3
# cgi-bin/telegram-bot.py

import sys
import os
import json
import cgi

# Добавьте путь к вашим модулям
sys.path.insert(0, '/home/username/taskflow/backend')

from telegram_bot.index import handler

# Получите данные из CGI
form = cgi.FieldStorage()
body = sys.stdin.read()

# Создайте event
event = {
    'httpMethod': os.environ.get('REQUEST_METHOD', 'POST'),
    'headers': dict(os.environ),
    'body': body,
    'queryStringParameters': {},
    'isBase64Encoded': False
}

# Mock context
class Context:
    request_id = 'cgi-request'
    
context = Context()

# Вызовите handler
result = handler(event, context)

# Верните результат
print("Content-Type: application/json")
print(f"Status: {result.get('statusCode', 200)}")
print()
print(result.get('body', '{}'))
```

**Примечание:** Shared hosting обычно не подходит для production из-за ограничений.

---

## Вариант C: Serverless Functions

### AWS Lambda:

1. **Создайте Lambda функцию для каждого backend модуля**:

```bash
# Упакуйте каждую функцию
cd backend/telegram-bot
zip -r telegram-bot.zip index.py requirements.txt
```

2. **Загрузите в AWS Lambda**
3. **Создайте API Gateway** с роутами:
   - POST /api/telegram-bot → Lambda: telegram-bot
   - POST /api/notify-task → Lambda: notify-task
   - POST /api/sync-task → Lambda: sync-task
   - POST /api/save-task → Lambda: save-task

4. **Настройте переменные окружения** в каждой Lambda:
   - `TELEGRAM_BOT_TOKEN`
   - `DATABASE_URL`

5. **Настройте RDS PostgreSQL** для базы данных

---

## Настройка базы данных

### Если используете Docker:
База настраивается автоматически через `docker-compose.yml`.

### Если используете внешний PostgreSQL:

```bash
# Подключитесь к PostgreSQL
psql -h your-db-host -U your-db-user -d your-db-name

# Выполните миграцию
\i /path/to/db_migrations/V0001__create_initial_schema.sql

# Или скопируйте SQL и вставьте вручную
```

### Строка подключения:

```bash
# Формат
postgresql://username:password@host:port/database

# Пример
postgresql://taskflow_user:SecurePass123@db.example.com:5432/taskflow_db
```

---

## Настройка Telegram бота

### 1. Создайте бота:
```
@BotFather → /newbot → следуйте инструкциям
```

### 2. Сохраните токен

### 3. Настройте webhook:

**Через веб-интерфейс:**
- Откройте TaskFlow → Настройки → введите токен → "Настроить Webhook"

**Через curl:**
```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-domain.com/api/telegram-bot",
    "allowed_updates": ["message", "callback_query"]
  }'
```

---

## Проверка работоспособности

### 1. Проверьте Docker контейнеры:
```bash
docker-compose ps
# Все должны быть "Up"
```

### 2. Проверьте базу данных:
```bash
docker exec -it taskflow-db psql -U taskflow_user -d taskflow_db -c "\dt"
# Должны быть таблицы: users, tasks, task_assignments
```

### 3. Проверьте backend:
```bash
curl https://your-domain.com/health
# Ответ: {"status":"ok","service":"taskflow-backend"}
```

### 4. Проверьте frontend:
```bash
curl -I https://your-domain.com
# HTTP/1.1 200 OK
```

### 5. Проверьте webhook:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
# url должен быть установлен
```

### 6. Проверьте Telegram бота:
- Откройте бота в Telegram
- Отправьте `/start test@example.com`
- Должен прийти ответ

### 7. Создайте тестовую задачу:
- В TaskFlow создайте задачу
- Назначьте на пользователя с подключенным Telegram
- Проверьте, что пришло уведомление

---

## Управление сервисом

### Запуск/остановка:
```bash
docker-compose start   # Запустить
docker-compose stop    # Остановить
docker-compose restart # Перезапустить
```

### Логи:
```bash
docker-compose logs -f              # Все логи
docker-compose logs -f backend      # Только backend
docker-compose logs -f postgres     # Только БД
```

### Обновление кода:
```bash
cd /opt/taskflow

# Обновите код
git pull
# ИЛИ загрузите новые файлы

# Пересоберите и перезапустите
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Резервное копирование БД:
```bash
# Бэкап
docker exec taskflow-db pg_dump -U taskflow_user taskflow_db > backup.sql

# Восстановление
docker exec -i taskflow-db psql -U taskflow_user -d taskflow_db < backup.sql
```

---

## Мониторинг

### Установите Grafana + Prometheus (опционально):

```yaml
# Добавьте в docker-compose.yml
  prometheus:
    image: prom/prometheus
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana
    ports:
      - "3001:3000"
    depends_on:
      - prometheus
```

---

## Безопасность

1. **Firewall:**
```bash
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw enable
```

2. **Fail2Ban:**
```bash
apt install fail2ban -y
systemctl enable fail2ban
```

3. **Регулярные обновления:**
```bash
apt update && apt upgrade -y
docker-compose pull
docker-compose up -d
```

4. **Secrets:**
- Не храните токены в коде
- Используйте .env файл
- Добавьте .env в .gitignore

---

## Готово! 🎉

Ваш TaskFlow развернут на собственном хостинге!

**Следующие шаги:**
1. Настройте мониторинг
2. Настройте резервное копирование
3. Протестируйте все функции
4. Добавьте пользователей

**Нужна помощь?**  
Сообщество: https://t.me/+QgiLIa1gFRY4Y2Iy
