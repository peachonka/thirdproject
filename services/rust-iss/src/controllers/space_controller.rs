use axum::{extract::State, Json};
use std::sync::Arc;

pub async fn space_latest_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn space_refresh_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn space_summary_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}
