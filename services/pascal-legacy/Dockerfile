FROM alpine:latest

# Устанавливаем зависимости
RUN apk add --no-cache \
    fpc \
    postgresql-client \
    curl \
    python3 \
    py3-pip \
    bash \
    tzdata

# Устанавливаем Python библиотеки для Excel
RUN pip3 install pandas openpyxl

# Рабочая директория
WORKDIR /app

# Копируем исходный код
COPY legacy.pas .

# Компилируем Pascal программу
RUN fpc -O3 -XX -Xs -Sd legacy.pas

# Создаем директории
RUN mkdir -p /app/output /app/logs

# Настройка времени
ENV TZ=UTC

# Запускаем программу
CMD ["./legacy"]