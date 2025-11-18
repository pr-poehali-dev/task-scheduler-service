# 📦 Развертывание TaskFlow на собственном хостинге

## Три способа развертывания

### 🚀 Способ 1: Автоматическая установка (рекомендуется)

**Для Ubuntu/Debian VPS**

```bash
# 1. Скачайте скрипт установки
wget https://raw.githubusercontent.com/your-repo/taskflow/main/setup.sh

# 2. Запустите с правами root
sudo bash setup.sh

# 3. Следуйте инструкциям на экране
# Скрипт автоматически:
# - Установит Docker и зависимости
# - Настроит базу данных
# - Получит SSL сертификат
# - Запустит все сервисы
# - Настроит Telegram webhook
```

**Что вам понадобится:**
- Доменное имя (например: taskflow.example.com)
- Токен от @BotFather
- 5-10 минут времени

---

### 🐳 Способ 2: Docker Compose вручную

**1. Подготовьте сервер:**

```bash
# Установите Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Установите Docker Compose
apt install docker-compose -y
```

**2. Загрузите проект:**

```bash
# Создайте директорию
mkdir -p /opt/taskflow
cd /opt/taskflow

# Загрузите файлы проекта (через git, scp или ftp)
git clone your-repo-url .
```

**3. Настройте переменные окружения:**

```bash
# Скопируйте пример и отредактируйте
cp .env.example .env
nano .env

# Укажите:
# - POSTGRES_PASSWORD=ваш_пароль
# - TELEGRAM_BOT_TOKEN=токен_от_BotFather
```

**4. Соберите frontend:**

```bash
# Локально на вашем компьютере
npm install
npm run build

# Загрузите dist/ на сервер в /opt/taskflow/dist/
```

**5. Получите SSL сертификат:**

```bash
# Установите Certbot
apt install certbot -y

# Получите сертификат
certbot certonly --standalone -d your-domain.com

# Скопируйте в проект
mkdir -p /opt/taskflow/ssl
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem /opt/taskflow/ssl/
cp /etc/letsencrypt/live/your-domain.com/privkey.pem /opt/taskflow/ssl/
```

**6. Обновите конфигурацию:**

```bash
# Замените server_name в nginx.conf
nano nginx.conf
# Измените: server_name _; → server_name your-domain.com;
```

**7. Запустите:**

```bash
docker-compose up -d
```

**8. Настройте webhook:**

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-domain.com/api/telegram-bot",
    "allowed_updates": ["message", "callback_query"]
  }'
```

---

### ⚙️ Способ 3: Без Docker (traditional hosting)

Если ваш хостинг не поддерживает Docker, следуйте инструкции в **DEPLOY_TO_OWN_HOSTING.md**, раздел "Вариант B: Shared Hosting".

---

## Структура файлов для развертывания

```
/opt/taskflow/
├── backend/                    # Backend функции
│   ├── telegram-bot/
│   ├── notify-task/
│   ├── sync-task/
│   └── save-task/
├── db_migrations/              # SQL миграции
│   └── V0001__create_initial_schema.sql
├── dist/                       # Собранный frontend (React)
├── ssl/                        # SSL сертификаты
│   ├── fullchain.pem
│   └── privkey.pem
├── docker-compose.yml          # Docker Compose конфигурация
├── Dockerfile.backend          # Dockerfile для backend
├── server.py                   # API Gateway (FastAPI)
├── nginx.conf                  # Nginx конфигурация
├── .env                        # Переменные окружения (секреты)
└── setup.sh                    # Скрипт автоустановки
```

---

## Управление сервисом

### После установки через скрипт:

```bash
taskflow start      # Запустить
taskflow stop       # Остановить
taskflow restart    # Перезапустить
taskflow logs       # Просмотр логов
taskflow status     # Статус сервисов
taskflow update     # Обновить
taskflow backup     # Бэкап базы данных
```

### При ручной установке:

```bash
cd /opt/taskflow

docker-compose start     # Запустить
docker-compose stop      # Остановить
docker-compose restart   # Перезапустить
docker-compose logs -f   # Логи
docker-compose ps        # Статус

# Бэкап БД
docker exec taskflow-db pg_dump -U taskflow_user taskflow_db > backup.sql

# Восстановление
docker exec -i taskflow-db psql -U taskflow_user -d taskflow_db < backup.sql
```

---

## Обновление кода

### Frontend:

```bash
# Локально
git pull
npm install
npm run build

# На сервере
scp -r dist/* root@your-server:/opt/taskflow/dist/
docker-compose restart nginx
```

### Backend:

```bash
# На сервере
cd /opt/taskflow
git pull
docker-compose build --no-cache backend
docker-compose up -d backend
```

---

## Проверка работоспособности

### 1. Проверьте Docker контейнеры:
```bash
docker-compose ps
# Все должны быть "Up"
```

### 2. Проверьте backend:
```bash
curl https://your-domain.com/health
# Ответ: {"status":"ok","service":"taskflow-backend"}
```

### 3. Проверьте frontend:
```bash
curl -I https://your-domain.com
# HTTP/1.1 200 OK
```

### 4. Проверьте webhook:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
# url должен быть установлен
```

### 5. Проверьте базу данных:
```bash
docker exec taskflow-db psql -U taskflow_user -d taskflow_db -c "\dt"
# Должны быть таблицы: users, tasks, task_assignments
```

---

## Мониторинг

### Логи в реальном времени:

```bash
# Все логи
docker-compose logs -f

# Только backend
docker-compose logs -f backend

# Только база данных
docker-compose logs -f postgres

# Только nginx
docker-compose logs -f nginx
```

### Использование ресурсов:

```bash
docker stats
```

---

## Решение проблем

### Проблема: Контейнер не запускается

```bash
# Проверьте логи
docker-compose logs backend

# Пересоберите контейнер
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Проблема: База данных недоступна

```bash
# Проверьте статус
docker-compose ps postgres

# Проверьте логи
docker-compose logs postgres

# Перезапустите
docker-compose restart postgres
```

### Проблема: SSL сертификат истек

```bash
# Обновите сертификат
certbot renew

# Скопируйте новый
cp /etc/letsencrypt/live/your-domain.com/*.pem /opt/taskflow/ssl/

# Перезапустите nginx
docker-compose restart nginx
```

### Проблема: Webhook не работает

```bash
# Проверьте статус webhook
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"

# Удалите старый webhook
curl "https://api.telegram.org/bot<TOKEN>/deleteWebhook"

# Установите новый
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://your-domain.com/api/telegram-bot"}'
```

---

## Безопасность

### 1. Firewall (UFW):

```bash
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw enable
```

### 2. Регулярные обновления:

```bash
# Обновляйте систему
apt update && apt upgrade -y

# Обновляйте Docker образы
docker-compose pull
docker-compose up -d
```

### 3. Бэкапы:

Настройте автоматические бэкапы базы данных:

```bash
# Добавьте в crontab
crontab -e

# Бэкап каждый день в 3:00
0 3 * * * docker exec taskflow-db pg_dump -U taskflow_user taskflow_db > /opt/taskflow/backups/backup-$(date +\%Y\%m\%d).sql
```

### 4. Secrets:

- Никогда не коммитьте .env в git
- Используйте сильные пароли
- Регулярно меняйте пароли

---

## Производительность

### Масштабирование backend:

Увеличьте количество worker'ов в `Dockerfile.backend`:

```dockerfile
CMD ["uvicorn", "server:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "4"]
```

### Увеличение ресурсов:

В `docker-compose.yml`:

```yaml
backend:
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
```

---

## Дополнительные возможности

### Добавление мониторинга (Grafana + Prometheus):

Добавьте в `docker-compose.yml`:

```yaml
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

### Настройка бэкапов в S3/MinIO:

```bash
# Установите AWS CLI
apt install awscli -y

# Настройте credentials
aws configure

# Бэкап в S3
docker exec taskflow-db pg_dump -U taskflow_user taskflow_db | gzip | \
  aws s3 cp - s3://your-bucket/backups/taskflow-$(date +%Y%m%d).sql.gz
```

---

## Документация

- **DEPLOY_TO_OWN_HOSTING.md** - Полная техническая инструкция
- **QUICK_START.md** - Быстрый старт за 15 минут
- **HOSTING_SETUP_GUIDE.md** - Детальная настройка Telegram бота

---

## Поддержка

Если возникли проблемы:
1. Проверьте логи: `docker-compose logs -f`
2. Проверьте документацию выше
3. Задайте вопрос в сообществе: https://t.me/+QgiLIa1gFRY4Y2Iy

---

## Чеклист готовности к production

- [ ] Docker и Docker Compose установлены
- [ ] SSL сертификат получен и настроен
- [ ] Firewall настроен (только 22, 80, 443 открыты)
- [ ] .env файл создан с секретами
- [ ] База данных создана и миграции применены
- [ ] Frontend собран и загружен в dist/
- [ ] Все контейнеры запущены (docker-compose ps)
- [ ] Backend отвечает на /health
- [ ] Frontend доступен в браузере
- [ ] Telegram webhook настроен
- [ ] Тестовая задача создана и уведомление получено
- [ ] Автообновление SSL настроено
- [ ] Автоматические бэкапы БД настроены
- [ ] Мониторинг настроен (опционально)

---

**Поздравляем! 🎉**

Ваш TaskFlow полностью развернут и готов к использованию!
