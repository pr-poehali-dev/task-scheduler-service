@echo off
chcp 65001 >nul
echo ╔═══════════════════════════════════════════════════════════════╗
echo ║         TaskFlow - Подготовка к загрузке на хостинг          ║
echo ╚═══════════════════════════════════════════════════════════════╝
echo.

REM Проверка npm
where npm >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Ошибка: npm не установлен!
    echo Установите Node.js с https://nodejs.org
    pause
    exit /b 1
)

echo ✅ Node.js найден
echo.

REM Установка зависимостей
echo 📦 Установка зависимостей...
call npm install
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Ошибка установки зависимостей
    pause
    exit /b 1
)

echo ✅ Зависимости установлены
echo.

REM Сборка проекта
echo 🔨 Сборка проекта...
call npm run build
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Ошибка сборки проекта
    pause
    exit /b 1
)

echo ✅ Проект собран
echo.

REM Создание папки для загрузки
echo 📁 Создание папки ready-to-upload...
if exist ready-to-upload rmdir /s /q ready-to-upload
mkdir ready-to-upload

REM Копирование файлов
echo 📋 Копирование файлов...

REM Frontend из dist
xcopy /E /I /Y dist ready-to-upload >nul

REM Backend API
xcopy /E /I /Y api ready-to-upload\api >nul

REM Конфигурация
copy /Y config.php ready-to-upload\ >nul
copy /Y .htaccess ready-to-upload\ >nul
copy /Y database.sql ready-to-upload\ >nul
copy /Y setup-telegram.php ready-to-upload\ >nul

REM Создание README
(
echo ╔═══════════════════════════════════════════════════════════════╗
echo ║                   ГОТОВО К ЗАГРУЗКЕ                           ║
echo ╚═══════════════════════════════════════════════════════════════╝
echo.
echo 📁 Эта папка содержит ВСЕ файлы для загрузки на хостинг
echo.
echo 🚀 ЧТО ДЕЛАТЬ:
echo.
echo 1. Загрузите ВСЕ файлы из этой папки в корень сайта ^(public_html/^)
echo    через FTP или файловый менеджер хостинга
echo.
echo 2. Откройте phpMyAdmin:
echo    - Создайте БД: taskflow
echo    - Импортируйте database.sql
echo.
echo 3. Откройте config.php на хостинге и настройте:
echo    - DB_HOST, DB_NAME, DB_USER, DB_PASS
echo    - BASE_URL ^(ваш домен^)
echo.
echo 4. Откройте ваш сайт: https://ваш-домен.ru
echo.
echo 5. Войдите:
echo    Email: alex@company.ru
echo    Пароль: admin123
echo.
echo ✅ ГОТОВО!
echo.
echo После установки:
echo - Удалите database.sql
echo - Удалите setup-telegram.php
echo - Смените пароли пользователей
echo.
echo 📖 Подробная инструкция: INSTALL.md в корне проекта
) > ready-to-upload\README.txt

echo ✅ Файлы скопированы
echo.

echo ╔═══════════════════════════════════════════════════════════════╗
echo ║                        ГОТОВО! ✅                             ║
echo ╚═══════════════════════════════════════════════════════════════╝
echo.
echo 📁 Все файлы готовы в папке: ready-to-upload\
echo.
echo 🚀 Что дальше:
echo 1. Откройте папку ready-to-upload\
echo 2. Загрузите ВСЕ файлы на хостинг через FTP
echo 3. Следуйте инструкции в ready-to-upload\README.txt
echo.
echo ✨ Удачи!
echo.
pause
