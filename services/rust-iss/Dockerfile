# services/rust-iss/Dockerfile
FROM rust:1.75-slim-bookworm AS builder

WORKDIR /app

# Кэшируем зависимости
COPY Cargo.toml Cargo.lock ./
RUN mkdir src && echo "fn main() {}" > src/main.rs
RUN cargo build --release
RUN rm -rf src

# Копируем реальный код
COPY . .

# Собираем
RUN cargo build --release

# Финальный образ
FROM debian:bookworm-slim
RUN apt-get update && apt-get install -y \
    libssl3 ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY --from=builder /app/target/release/rust-iss /usr/local/bin/

CMD ["rust-iss"]