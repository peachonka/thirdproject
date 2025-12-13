use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct TelemetryRecord {
    pub recorded_at: String,
    pub voltage: f64,
    pub temperature: f64,
    pub pressure: f64,
    pub is_operational: bool,
    pub error_code: i32,
    pub source_file: String,
}