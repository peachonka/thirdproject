#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html"
PATCH_DIR="/opt/laravel-patches"

echo "[php] init start"

# Проверяем, есть ли уже Laravel (файл artisan)
if [ ! -f "$APP_DIR/artisan" ]; then
  echo "[php] creating laravel skeleton"
  
  # Если директория не пустая, очистим её
  if [ "$(ls -A $APP_DIR)" ]; then
    echo "[php] directory not empty, cleaning..."
    rm -rf $APP_DIR/*
  fi
  
  # Создаём новый Laravel проект
  composer create-project --no-interaction --prefer-dist laravel/laravel:^11 "$APP_DIR"
  
  # Копируем .env если нужно
  if [ -f "$APP_DIR/.env.example" ]; then
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    sed -i 's|APP_NAME=Laravel|APP_NAME=ISSOSDR|g' "$APP_DIR/.env" || true
  fi
  
  # Генерируем ключ
  if [ -f "$APP_DIR/artisan" ]; then
    php "$APP_DIR/artisan" key:generate || true
  fi
fi

# Применяем патчи если есть
if [ -d "$PATCH_DIR" ]; then
  echo "[php] applying patches"
  rsync -a "$PATCH_DIR/" "$APP_DIR/"
fi

# Настраиваем права
chown -R www-data:www-data "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true

echo "[php] starting php-fpm"
exec php-fpm -F