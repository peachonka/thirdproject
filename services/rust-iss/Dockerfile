# services/rust-iss/Dockerfile
FROM ubuntu:22.04 AS builder

# Установи Rust nightly или beta (они поддерживают edition 2024)
RUN apt-get update && \
    apt-get install -y curl gcc libssl-dev pkg-config && \
    curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y --default-toolchain nightly

ENV PATH="/root/.cargo/bin:${PATH}"

WORKDIR /app
COPY . .

# Обнови зависимости до совместимых версий
RUN cargo update -p home --precise "0.5.5"  # Используем старую версию без edition 2024

RUN cargo build --release

FROM ubuntu:22.04
RUN apt-get update && apt-get install -y libssl3 && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=builder /app/target/release/rust_iss /app/rust_iss

EXPOSE 3001
CMD ["./rust_iss"]