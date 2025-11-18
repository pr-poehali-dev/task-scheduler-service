#!/bin/bash

# TaskFlow - Скрипт автоматической установки на VPS
# Использование: bash setup.sh

set -e

echo "🚀 TaskFlow - Установка на VPS"
echo "================================"
echo ""

# Проверка прав root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Запустите скрипт с правами root: sudo bash setup.sh"
    exit 1
fi

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функции для вывода
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# 1. Обновление системы
echo ""
print_info "Обновление системы..."
apt update && apt upgrade -y
print_success "Система обновлена"

# 2. Установка Docker
echo ""
print_info "Установка Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    print_success "Docker установлен"
else
    print_success "Docker уже установлен"
fi

# 3. Установка Docker Compose
echo ""
print_info "Установка Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    apt install docker-compose -y
    print_success "Docker Compose установлен"
else
    print_success "Docker Compose уже установлен"
fi

# 4. Установка дополнительных утилит
echo ""
print_info "Установка дополнительных утилит..."
apt install -y curl wget git nano certbot python3-certbot-nginx ufw
print_success "Утилиты установлены"

# 5. Настройка Firewall
echo ""
print_info "Настройка Firewall..."
ufw --force enable
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
print_success "Firewall настроен"

# 6. Создание директории проекта
echo ""
print_info "Создание директории проекта..."
PROJECT_DIR="/opt/taskflow"
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR
print_success "Директория создана: $PROJECT_DIR"

# 7. Запрос данных от пользователя
echo ""
echo "================================"
echo "Настройка переменных окружения"
echo "================================"
echo ""

read -p "Введите домен (например: taskflow.example.com): " DOMAIN
read -sp "Введите пароль для PostgreSQL: " POSTGRES_PASSWORD
echo ""
read -p "Введите токен Telegram бота (от @BotFather): " TELEGRAM_BOT_TOKEN
echo ""

# 8. Создание .env файла
echo ""
print_info "Создание .env файла..."
cat > $PROJECT_DIR/.env <<EOF
POSTGRES_PASSWORD=$POSTGRES_PASSWORD
TELEGRAM_BOT_TOKEN=$TELEGRAM_BOT_TOKEN
DATABASE_URL=postgresql://taskflow_user:$POSTGRES_PASSWORD@postgres:5432/taskflow_db
PYTHON_ENV=production
LOG_LEVEL=info
EOF
print_success ".env файл создан"

# 9. Клонирование/копирование проекта
echo ""
print_info "Загрузите файлы проекта в директорию: $PROJECT_DIR"
print_info "Необходимые файлы:"
echo "  - docker-compose.yml"
echo "  - Dockerfile.backend"
echo "  - server.py"
echo "  - nginx.conf"
echo "  - backend/ (директория)"
echo "  - db_migrations/ (директория)"
echo "  - dist/ (собранный frontend)"
echo ""
read -p "Файлы загружены? (y/n): " files_ready

if [ "$files_ready" != "y" ]; then
    print_error "Загрузите файлы и запустите скрипт снова"
    exit 1
fi

# 10. Получение SSL сертификата
echo ""
print_info "Получение SSL сертификата от Let's Encrypt..."
print_info "Сначала запустим сервер для ACME challenge..."

# Временный nginx для получения сертификата
docker run -d --name temp-nginx \
    -p 80:80 \
    -v $PROJECT_DIR/dist:/usr/share/nginx/html \
    nginx:alpine

sleep 5

certbot certonly --webroot \
    -w $PROJECT_DIR/dist \
    -d $DOMAIN \
    --non-interactive \
    --agree-tos \
    --register-unsafely-without-email || print_error "Не удалось получить SSL сертификат"

docker stop temp-nginx && docker rm temp-nginx

# Копирование сертификатов
mkdir -p $PROJECT_DIR/ssl
cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem $PROJECT_DIR/ssl/
cp /etc/letsencrypt/live/$DOMAIN/privkey.pem $PROJECT_DIR/ssl/

print_success "SSL сертификат получен"

# 11. Обновление nginx.conf с доменом
echo ""
print_info "Обновление конфигурации Nginx..."
sed -i "s/server_name _;/server_name $DOMAIN;/g" $PROJECT_DIR/nginx.conf
print_success "Nginx сконфигурирован"

# 12. Запуск Docker Compose
echo ""
print_info "Запуск Docker контейнеров..."
cd $PROJECT_DIR
docker-compose up -d

print_success "Docker контейнеры запущены"

# 13. Ожидание запуска сервисов
echo ""
print_info "Ожидание запуска сервисов..."
sleep 15

# 14. Проверка статуса
echo ""
print_info "Проверка статуса сервисов..."
docker-compose ps

# 15. Настройка webhook
echo ""
print_info "Настройка Telegram webhook..."
WEBHOOK_URL="https://$DOMAIN/api/telegram-bot"

curl -X POST "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/setWebhook" \
    -H "Content-Type: application/json" \
    -d "{\"url\": \"$WEBHOOK_URL\", \"allowed_updates\": [\"message\", \"callback_query\"]}" \
    -s -o /dev/null

print_success "Webhook настроен"

# 16. Настройка автообновления SSL
echo ""
print_info "Настройка автообновления SSL..."
(crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet && cp /etc/letsencrypt/live/$DOMAIN/*.pem $PROJECT_DIR/ssl/ && cd $PROJECT_DIR && docker-compose restart nginx") | crontab -
print_success "Автообновление SSL настроено"

# 17. Создание скрипта для управления
echo ""
print_info "Создание скриптов управления..."

cat > /usr/local/bin/taskflow <<'EOF'
#!/bin/bash
cd /opt/taskflow

case "$1" in
    start)
        docker-compose start
        echo "TaskFlow запущен"
        ;;
    stop)
        docker-compose stop
        echo "TaskFlow остановлен"
        ;;
    restart)
        docker-compose restart
        echo "TaskFlow перезапущен"
        ;;
    logs)
        docker-compose logs -f
        ;;
    status)
        docker-compose ps
        ;;
    update)
        docker-compose pull
        docker-compose up -d
        echo "TaskFlow обновлен"
        ;;
    backup)
        BACKUP_FILE="backup-$(date +%Y%m%d-%H%M%S).sql"
        docker exec taskflow-db pg_dump -U taskflow_user taskflow_db > $BACKUP_FILE
        echo "Бэкап создан: $BACKUP_FILE"
        ;;
    *)
        echo "Использование: taskflow {start|stop|restart|logs|status|update|backup}"
        exit 1
        ;;
esac
EOF

chmod +x /usr/local/bin/taskflow
print_success "Команда 'taskflow' создана"

# 18. Итоговая информация
echo ""
echo "================================"
echo "🎉 Установка завершена!"
echo "================================"
echo ""
print_success "TaskFlow установлен и запущен"
echo ""
echo "📊 Информация о сервисе:"
echo "  URL: https://$DOMAIN"
echo "  Backend API: https://$DOMAIN/api/"
echo "  Health Check: https://$DOMAIN/health"
echo ""
echo "🔧 Команды управления:"
echo "  taskflow start    - Запустить"
echo "  taskflow stop     - Остановить"
echo "  taskflow restart  - Перезапустить"
echo "  taskflow logs     - Просмотр логов"
echo "  taskflow status   - Статус сервисов"
echo "  taskflow update   - Обновить"
echo "  taskflow backup   - Создать бэкап БД"
echo ""
echo "📝 Следующие шаги:"
echo "  1. Откройте https://$DOMAIN в браузере"
echo "  2. Войдите как администратор (alex@company.ru / admin123)"
echo "  3. Перейдите в Настройки и проверьте webhook"
echo "  4. Создайте тестовую задачу"
echo ""
echo "📚 Документация:"
echo "  - DEPLOY_TO_OWN_HOSTING.md"
echo "  - QUICK_START.md"
echo ""
print_success "Готово! 🚀"
