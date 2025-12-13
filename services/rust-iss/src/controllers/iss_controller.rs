use axum::{extract::State, Json};
use std::sync::Arc;

pub async fn last_iss_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn trigger_iss_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn iss_trend_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}
