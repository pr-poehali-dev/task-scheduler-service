#!/bin/bash

echo "═══════════════════════════════════════════════════════════════"
echo "   Копирование файлов билда в ready-to-upload/"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Проверяем существование папки билда
if [ ! -d "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/" ]; then
    echo "❌ Ошибка: Папка билда не найдена!"
    echo "   Сначала соберите проект: npm run build"
    exit 1
fi

# Проверяем существование папки ready-to-upload
if [ ! -d "ready-to-upload/" ]; then
    echo "❌ Ошибка: Папка ready-to-upload не найдена!"
    exit 1
fi

echo "📂 Копирую файлы из билда..."
echo ""

# Копируем папку assets
echo "▶ Копирование assets/"
cp -r "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/assets" "ready-to-upload/"
if [ $? -eq 0 ]; then
    echo "✅ assets/ скопирована"
else
    echo "❌ Ошибка копирования assets/"
    exit 1
fi

# Копируем favicon
echo "▶ Копирование favicon.svg"
cp "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/favicon.svg" "ready-to-upload/"
if [ $? -eq 0 ]; then
    echo "✅ favicon.svg скопирован"
else
    echo "❌ Ошибка копирования favicon.svg"
fi

# Копируем placeholder
echo "▶ Копирование placeholder.svg"
cp "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/placeholder.svg" "ready-to-upload/"
if [ $? -eq 0 ]; then
    echo "✅ placeholder.svg скопирован"
else
    echo "❌ Ошибка копирования placeholder.svg"
fi

# Копируем robots.txt
echo "▶ Копирование robots.txt"
cp "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/robots.txt" "ready-to-upload/"
if [ $? -eq 0 ]; then
    echo "✅ robots.txt скопирован"
else
    echo "❌ Ошибка копирования robots.txt"
fi

# Копируем папку 4x если она есть
if [ -d "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/4x/" ]; then
    echo "▶ Копирование 4x/"
    cp -r "builds/d5613235f1424a4ff5e396cc6f541e0e1e83e104/6501a/4x" "ready-to-upload/"
    if [ $? -eq 0 ]; then
        echo "✅ 4x/ скопирована"
    else
        echo "❌ Ошибка копирования 4x/"
    fi
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✅ ГОТОВО! Все файлы скопированы в ready-to-upload/"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📁 Содержимое папки ready-to-upload/ готово для загрузки на хостинг:"
echo ""
ls -1 ready-to-upload/
echo ""
echo "📋 Следующие шаги:"
echo "   1. Настройте config.php (база данных и домен)"
echo "   2. Импортируйте database.sql в phpMyAdmin"
echo "   3. Загрузите всю папку ready-to-upload/ на хостинг"
echo "   4. Откройте ваш сайт в браузере"
echo ""
echo "📖 Подробная инструкция: ready-to-upload/ИНСТРУКЦИЯ.txt"
echo ""
