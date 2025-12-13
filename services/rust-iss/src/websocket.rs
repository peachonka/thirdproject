use axum::{
    extract::{
        ws::{Message, WebSocket, WebSocketUpgrade},
        State,
    },
    response::IntoResponse,
};
use futures::{sink::SinkExt, stream::StreamExt};
use serde_json::json;
use std::sync::Arc;
use tokio::sync::broadcast;
use tokio::time::{interval, Duration};
use sqlx::Row; 

use crate::AppState;

// Структура для WebSocket сообщений
#[derive(Clone, serde::Serialize)]
pub struct IssUpdate {
    pub latitude: f64,
    pub longitude: f64,
    pub altitude: f64,
    pub velocity: f64,
    pub timestamp: String,
    pub visibility: String,
    pub solar_lat: Option<f64>,
    pub solar_lon: Option<f64>,
}

// Канал для broadcast обновлений ISS
pub type IssBroadcaster = broadcast::Sender<IssUpdate>;

// Инициализация WebSocket handler
pub async fn ws_handler(
    ws: WebSocketUpgrade,
    State(state): State<Arc<AppState>>,
) -> impl IntoResponse {
    ws.on_upgrade(|socket| handle_socket(socket, state))
}

// Обработчик WebSocket соединения
async fn handle_socket(socket: WebSocket, state: Arc<AppState>) {
    let (mut sender, mut receiver) = socket.split();
    
    // Подписываемся на обновления ISS
    let mut rx = state.iss_broadcaster.new_receiver();
    
    // Отправляем текущую позицию при подключении
    if let Ok(current) = get_current_iss_position(&state).await {
        let _ = sender
            .send(Message::Text(
                serde_json::to_string(&json!({
                    "type": "init",
                    "data": current
                }))
                .unwrap(),
            ))
            .await;
    }
    
    // Задача: отправка обновлений от broadcast канала
    let send_task = tokio::spawn(async move {
        while let Ok(update) = rx.recv().await {
            let message = Message::Text(
                serde_json::to_string(&json!({
                    "type": "update",
                    "data": update
                }))
                .unwrap(),
            );
            
            if sender.send(message).await.is_err() {
                break; // Клиент отключился
            }
        }
    });
    
    // Задача: прием сообщений от клиента
    let recv_task = tokio::spawn(async move {
        while let Some(Ok(message)) = receiver.next().await {
            match message {
                Message::Text(text) => {
                    // Обработка команд от клиента
                    handle_client_message(&text).await;
                }
                Message::Close(_) => {
                    break;
                }
                _ => {}
            }
        }
    });
    
    // Ждем завершения одной из задач
    tokio::select! {
        _ = send_task => {}
        _ = recv_task => {}
    }
}

// Получение текущей позиции ISS
async fn get_current_iss_position(state: &Arc<AppState>) -> Result<IssUpdate, sqlx::Error> {
    let row = sqlx::query(
        "SELECT payload, fetched_at FROM iss_fetch_log 
         ORDER BY fetched_at DESC LIMIT 1"
    )
    .fetch_one(&state.pool)
    .await?;
    
    let payload: serde_json::Value = row.get("payload");
    let fetched_at: String = row.get("fetched_at");
    
    Ok(IssUpdate {
        latitude: payload["latitude"].as_f64().unwrap_or(0.0),
        longitude: payload["longitude"].as_f64().unwrap_or(0.0),
        altitude: payload["altitude"].as_f64().unwrap_or(0.0),
        velocity: payload["velocity"].as_f64().unwrap_or(0.0),
        visibility: payload["visibility"].as_str().unwrap_or("").to_string(),
        timestamp: fetched_at,
        solar_lat: None,
        solar_lon: None,
    })
}

// Обработка сообщений от клиента
async fn handle_client_message(text: &str) {
    if let Ok(value) = serde_json::from_str::<serde_json::Value>(text) {
        if let Some(cmd) = value.get("command").and_then(|v| v.as_str()) {
            match cmd {
                "ping" => {
                    println!("WebSocket ping received");
                }
                "subscribe" => {
                    println!("Client subscribed to updates");
                }
                _ => {}
            }
        }
    }
}

// Фоновая задача для периодических обновлений ISS
pub async fn start_iss_broadcaster(state: Arc<AppState>) {
    let mut interval = interval(Duration::from_secs(5)); // Обновление каждые 5 секунд
    
    loop {
        interval.tick().await;
        
        if let Ok(position) = get_current_iss_position(&state).await {
            // Отправляем обновление всем подключенным клиентам
            let _ = state.iss_broadcaster.try_broadcast(position.clone());
        }
    }
}