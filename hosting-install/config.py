"""
TaskFlow - Конфигурационный файл для shared hosting
Используйте этот файл вместо .env для настройки переменных окружения
"""

# ========================================
# ОБЯЗАТЕЛЬНЫЕ НАСТРОЙКИ
# ========================================

# Telegram Bot Token (получите у @BotFather)
TELEGRAM_BOT_TOKEN = "1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"

# PostgreSQL Database Connection String
# Формат: postgresql://username:password@host:port/database
DATABASE_URL = "postgresql://taskflow_user:your_password@localhost:5432/taskflow_db"

# Путь к папке backend на хостинге
# Узнайте у хостера или через phpinfo() -> $_SERVER['DOCUMENT_ROOT']
BACKEND_PATH = "/home/username/backend"

# ========================================
# ОПЦИОНАЛЬНЫЕ НАСТРОЙКИ
# ========================================

# Окружение (production/development)
PYTHON_ENV = "production"

# Уровень логирования (debug/info/warning/error)
LOG_LEVEL = "info"

# Таймауты (секунды)
REQUEST_TIMEOUT = 30
DATABASE_TIMEOUT = 10

# ========================================
# ИНСТРУКЦИИ ПО НАСТРОЙКЕ
# ========================================

"""
1. TELEGRAM_BOT_TOKEN:
   - Откройте @BotFather в Telegram
   - Отправьте /newbot
   - Скопируйте токен и вставьте выше

2. DATABASE_URL:
   - В панели хостинга создайте PostgreSQL базу
   - Узнайте: username, password, host, port, database name
   - Соберите строку: postgresql://username:password@host:port/database
   
   Примеры:
   - Локальная БД: postgresql://taskflow:pass123@localhost:5432/taskflow_db
   - Удаленная БД: postgresql://user:pass@db.example.com:5432/mydb

3. BACKEND_PATH:
   - Полный путь к папке backend на сервере
   - НЕ путь в браузере, а файловый путь на сервере!
   
   Как узнать:
   - Через SSH: pwd в папке backend
   - Через PHP: echo $_SERVER['DOCUMENT_ROOT'];
   - Спросите у хостера
   
   Примеры:
   - /home/username/backend
   - /var/www/html/backend
   - /home/u12345/domains/example.com/backend

4. После настройки:
   - Сохраните файл
   - Скопируйте настройки в каждый CGI скрипт (cgi-bin/*.py)
   - Или импортируйте этот файл в CGI скриптах
"""

# ========================================
# ПРОВЕРКА НАСТРОЕК
# ========================================

def validate_config():
    """Проверяет корректность конфигурации"""
    errors = []
    
    # Проверка токена
    if TELEGRAM_BOT_TOKEN == "1234567890:ABCdefGHIjklMNOpqrsTUVwxyz":
        errors.append("❌ TELEGRAM_BOT_TOKEN не настроен! Замените на реальный токен от @BotFather")
    
    if ":" not in TELEGRAM_BOT_TOKEN:
        errors.append("❌ TELEGRAM_BOT_TOKEN имеет неверный формат")
    
    # Проверка DATABASE_URL
    if DATABASE_URL == "postgresql://taskflow_user:your_password@localhost:5432/taskflow_db":
        errors.append("❌ DATABASE_URL не настроен! Замените на реальную строку подключения")
    
    if not DATABASE_URL.startswith("postgresql://"):
        errors.append("❌ DATABASE_URL должен начинаться с postgresql://")
    
    # Проверка BACKEND_PATH
    if BACKEND_PATH == "/home/username/backend":
        errors.append("⚠️  BACKEND_PATH не настроен! Замените username на ваше имя пользователя")
    
    import os
    if not os.path.exists(BACKEND_PATH):
        errors.append(f"❌ Папка {BACKEND_PATH} не найдена на сервере!")
    
    # Вывод результатов
    if errors:
        print("=" * 60)
        print("ОШИБКИ КОНФИГУРАЦИИ:")
        print("=" * 60)
        for error in errors:
            print(error)
        print("=" * 60)
        return False
    else:
        print("✅ Конфигурация корректна!")
        return True

# Проверка при импорте (закомментируйте в production)
if __name__ == "__main__":
    validate_config()
