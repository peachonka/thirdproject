use axum::{extract::State, Json};
use std::sync::Arc;

pub async fn handle_legacy_notification() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn get_legacy_stats() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn export_legacy_data() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}
