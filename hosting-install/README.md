# 📦 TaskFlow - Установка на shared hosting

Эта папка содержит готовые файлы для установки TaskFlow на обычный хостинг через файловый менеджер.

## 📁 Содержимое

```
hosting-install/
├── cgi-bin/                    # CGI обертки для backend функций
│   ├── telegram-bot.py        # ✅ Готовый файл
│   ├── notify-task.py         # ✅ Готовый файл
│   ├── sync-task.py           # ✅ Готовый файл
│   └── save-task.py           # ✅ Готовый файл
│
├── public_html/               # Конфигурация для frontend
│   └── .htaccess              # ✅ Готовый файл
│
├── config.py                  # 📝 Файл настроек (заполните!)
├── INSTALL_INSTRUCTIONS.md    # 📖 Инструкция по установке
└── README.md                  # 📄 Этот файл
```

## 🚀 Быстрый старт

### 1. Настройте config.py

Откройте `config.py` и заполните:

```python
TELEGRAM_BOT_TOKEN = "ваш_токен"
DATABASE_URL = "postgresql://user:pass@host:5432/db"
BACKEND_PATH = "/home/username/backend"  # Реальный путь!
```

### 2. Обновите CGI скрипты

В **каждом** файле `cgi-bin/*.py` замените первые строки:

```python
BACKEND_PATH = '/home/username/backend'  # Ваш путь
BOT_TOKEN = 'ваш_токен'
DATABASE_URL = 'postgresql://...'
```

### 3. Загрузите файлы

Через файловый менеджер хостинга:

**Frontend → public_html/**
- Соберите: `npm run build`
- Загрузите всё из `dist/`
- Загрузите `.htaccess` из `hosting-install/public_html/`

**Backend → cgi-bin/**
- Загрузите все 4 файла из `hosting-install/cgi-bin/`
- Установите права: **755**

**Модули → backend/**
- Загрузите папки из проекта: `backend/telegram-bot/`, `backend/notify-task/` и т.д.

### 4. Настройте базу данных

1. Создайте PostgreSQL базу в панели хостинга
2. Примените миграцию из `db_migrations/V0001__create_initial_schema.sql`

### 5. Установите зависимости

Через SSH:
```bash
cd backend/telegram-bot && pip3 install --user -r requirements.txt
cd ../notify-task && pip3 install --user -r requirements.txt
cd ../sync-task && pip3 install --user -r requirements.txt
cd ../save-task && pip3 install --user -r requirements.txt
```

### 6. Настройте webhook

Откройте в браузере (замените TOKEN и DOMAIN):
```
https://api.telegram.org/botTOKEN/setWebhook?url=https://DOMAIN/cgi-bin/telegram-bot.py
```

### 7. Готово!

Откройте ваш сайт и проверьте работу.

---

## 📖 Полная документация

Читайте **INSTALL_INSTRUCTIONS.md** для детальной пошаговой инструкции.

---

## 🔧 Что нужно изменить перед загрузкой

### В каждом CGI скрипте (4 файла):

```python
# ❌ ДО (по умолчанию):
BACKEND_PATH = '/home/username/backend'
BOT_TOKEN = 'YOUR_TELEGRAM_BOT_TOKEN'
DATABASE_URL = 'postgresql://user:pass@localhost:5432/taskflow_db'

# ✅ ПОСЛЕ (ваши данные):
BACKEND_PATH = '/home/mysite123/backend'  # Реальный путь на хостинге
BOT_TOKEN = '1234567890:ABCdefGHIjkl...'  # Токен от @BotFather
DATABASE_URL = 'postgresql://taskflow:SecurePass123@localhost:5432/taskflow_db'
```

### В frontend перед сборкой:

Отредактируйте `src/config/backend.ts`:

```typescript
export const BACKEND_URLS = {
  TELEGRAM_BOT: 'https://your-domain.com/cgi-bin/telegram-bot.py',
  NOTIFY_TASK: 'https://your-domain.com/cgi-bin/notify-task.py',
  SAVE_TASK: 'https://your-domain.com/cgi-bin/save-task.py',
  SYNC_TASK: 'https://your-domain.com/cgi-bin/sync-task.py'
} as const;
```

Затем: `npm run build`

---

## 📂 Где что разместить на хостинге

```
/home/username/
│
├── public_html/                    ← Основной веб-сайт
│   ├── index.html                 ← Из dist/
│   ├── assets/                    ← Из dist/assets/
│   └── .htaccess                  ← Из hosting-install/public_html/
│
├── cgi-bin/                       ← CGI скрипты
│   ├── telegram-bot.py            ← Из hosting-install/cgi-bin/
│   ├── notify-task.py
│   ├── sync-task.py
│   └── save-task.py
│   (Права: 755 для всех!)
│
└── backend/                       ← Backend модули
    ├── telegram-bot/              ← Из проекта backend/
    │   ├── index.py
    │   └── requirements.txt
    ├── notify-task/
    │   ├── index.py
    │   └── requirements.txt
    ├── sync-task/
    │   ├── index.py
    │   └── requirements.txt
    └── save-task/
        ├── index.py
        └── requirements.txt
```

---

## ⚙️ Права доступа

Обязательно установите правильные права через файловый менеджер:

| Файлы | Права | Описание |
|-------|-------|----------|
| `cgi-bin/*.py` | **755** | Исполняемые CGI скрипты |
| `backend/*/index.py` | **644** | Python модули |
| `public_html/*.html` | **644** | HTML файлы |
| `public_html/assets/*` | **644** | Статические файлы |
| Все папки | **755** | Директории |

**Как установить:**
1. Выделите файл в файловом менеджере
2. ПКМ → "Права доступа" или "Permissions"
3. Введите: 755 или 644

---

## ❗ Важно

### 1. BACKEND_PATH

Это **НЕ** URL в браузере, а **файловый путь** на сервере!

❌ Неправильно:
```python
BACKEND_PATH = 'https://mysite.com/backend'
BACKEND_PATH = '/backend'
```

✅ Правильно:
```python
BACKEND_PATH = '/home/mysite123/backend'
BACKEND_PATH = '/var/www/html/backend'
```

**Как узнать:**
- Создайте `test.php` в `public_html/`:
  ```php
  <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
  ```
- Откройте в браузере
- Если видите `/home/mysite123/public_html`, то `BACKEND_PATH = /home/mysite123/backend`

### 2. Python зависимости

Если `pip3 install` не работает:
1. Напишите в поддержку хостинга
2. Попросите установить: `psycopg2-binary` и `pydantic`

### 3. HTTPS обязателен

Telegram требует HTTPS для webhook. Убедитесь что ваш сайт работает по `https://`

---

## 🐛 Частые проблемы

### 500 Internal Server Error на CGI

**Причина:** Неправильные права или шебанг

**Решение:**
1. Установите права 755 для CGI скриптов
2. Проверьте первую строку: `#!/usr/bin/env python3`
3. Возможно нужно: `#!/usr/bin/python3` или `#!/usr/local/bin/python3`

### ModuleNotFoundError

**Причина:** Не установлены зависимости

**Решение:**
```bash
pip3 install --user psycopg2-binary pydantic
```

### can't open file 'index.py'

**Причина:** Неверный путь в BACKEND_PATH

**Решение:**
Проверьте, что:
1. Папка `backend/telegram-bot/` существует
2. Внутри есть файл `index.py`
3. Путь в CGI скрипте правильный: `/home/username/backend` (без слеша в конце)

---

## ✅ Чеклист перед загрузкой

- [ ] `config.py` заполнен
- [ ] Все 4 CGI скрипта обновлены (BACKEND_PATH, BOT_TOKEN, DATABASE_URL)
- [ ] Frontend собран (`npm run build`)
- [ ] Backend URLs обновлены в `src/config/backend.ts`
- [ ] Получен токен от @BotFather
- [ ] Создана PostgreSQL база
- [ ] Знаете свой BACKEND_PATH

---

## 📚 Документация

- **INSTALL_INSTRUCTIONS.md** - Подробная пошаговая инструкция
- **../INSTALL_VIA_FILE_MANAGER.md** - Полное руководство
- **../DEPLOY_TO_OWN_HOSTING.md** - Альтернативные способы установки

---

## 📞 Нужна помощь?

Сообщество: https://t.me/+QgiLIa1gFRY4Y2Iy

---

**Удачи! 🚀**
