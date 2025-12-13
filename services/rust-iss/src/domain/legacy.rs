use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};

#[derive(Debug, Deserialize)]
pub struct LegacyNotification {
    pub file_path: String,
    pub action: String, // "created", "updated", "deleted"
}

#[derive(Debug, Serialize)]
pub struct LegacyStats {
    pub total_files: i64,
    pub total_records: i64,
    pub last_file: Option<String>,
    pub last_import: Option<DateTime<Utc>>,
}

#[derive(Debug, Clone)]
pub struct TelemetryRecord {
    pub recorded_at: String,
    pub voltage: f64,
    pub temperature: f64,
    pub pressure: f64,
    pub is_operational: bool,
    pub error_code: i32,
    pub source_file: String,
}

#[derive(Debug, Clone)]
pub struct LegacyEvent {
    pub file_path: String,
    pub action: String,
    pub processed_at: DateTime<Utc>,
}