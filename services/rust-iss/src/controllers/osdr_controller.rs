use axum::{extract::State, Json};
use std::sync::Arc;

pub async fn osdr_sync_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}

pub async fn osdr_list_handler() -> Json<serde_json::Value> {
    Json(serde_json::json!({ "message": "TODO" }))
}
