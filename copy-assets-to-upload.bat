@echo off
chcp 65001 > nul
echo ═══════════════════════════════════════════════════════════════
echo    Копирование файлов билда в ready-to-upload/
echo ═══════════════════════════════════════════════════════════════
echo.

REM Проверяем существование папки билда
if not exist "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\" (
    echo ❌ Ошибка: Папка билда не найдена!
    echo    Сначала соберите проект: npm run build
    pause
    exit /b 1
)

REM Проверяем существование папки ready-to-upload
if not exist "ready-to-upload\" (
    echo ❌ Ошибка: Папка ready-to-upload не найдена!
    pause
    exit /b 1
)

echo 📂 Копирую файлы из билда...
echo.

REM Копируем папку assets
echo ▶ Копирование assets/
xcopy "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\assets\*" "ready-to-upload\assets\" /E /I /Y > nul
if errorlevel 1 (
    echo ❌ Ошибка копирования assets/
    pause
    exit /b 1
)
echo ✅ assets/ скопирована

REM Копируем favicon
echo ▶ Копирование favicon.svg
copy "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\favicon.svg" "ready-to-upload\favicon.svg" > nul
if errorlevel 1 (
    echo ❌ Ошибка копирования favicon.svg
) else (
    echo ✅ favicon.svg скопирован
)

REM Копируем placeholder
echo ▶ Копирование placeholder.svg
copy "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\placeholder.svg" "ready-to-upload\placeholder.svg" > nul
if errorlevel 1 (
    echo ❌ Ошибка копирования placeholder.svg
) else (
    echo ✅ placeholder.svg скопирован
)

REM Копируем robots.txt
echo ▶ Копирование robots.txt
copy "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\robots.txt" "ready-to-upload\robots.txt" > nul
if errorlevel 1 (
    echo ❌ Ошибка копирования robots.txt
) else (
    echo ✅ robots.txt скопирован
)

REM Копируем папку 4x если она есть
if exist "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\4x\" (
    echo ▶ Копирование 4x/
    xcopy "builds\d5613235f1424a4ff5e396cc6f541e0e1e83e104\6501a\4x\*" "ready-to-upload\4x\" /E /I /Y > nul
    if errorlevel 1 (
        echo ❌ Ошибка копирования 4x/
    ) else (
        echo ✅ 4x/ скопирована
    )
)

echo.
echo ═══════════════════════════════════════════════════════════════
echo ✅ ГОТОВО! Все файлы скопированы в ready-to-upload/
echo ═══════════════════════════════════════════════════════════════
echo.
echo 📁 Содержимое папки ready-to-upload/ готово для загрузки на хостинг:
echo.
dir /B ready-to-upload
echo.
echo 📋 Следующие шаги:
echo    1. Настройте config.php (база данных и домен)
echo    2. Импортируйте database.sql в phpMyAdmin
echo    3. Загрузите всю папку ready-to-upload/ на хостинг
echo    4. Откройте ваш сайт в браузере
echo.
echo 📖 Подробная инструкция: ready-to-upload/ИНСТРУКЦИЯ.txt
echo.
pause
