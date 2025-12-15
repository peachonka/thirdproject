// src/websocket.rs - временная заглушка

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

// Просто определяем тип для компиляции
pub type IssBroadcaster = tokio::sync::broadcast::Sender<IssUpdate>;