use serde_json::Value;
use validator::{Validate, ValidationError, ValidationErrors};
use chrono::{DateTime, Utc};
use std::collections::HashMap;

#[derive(Debug, Validate)]
pub struct IssData {
    #[validate(range(min = -90.0, max = 90.0))]
    pub latitude: f64,
    
    #[validate(range(min = -180.0, max = 180.0))]
    pub longitude: f64,
    
    #[validate(range(min = 0.0, max = 10000.0))]
    pub altitude: f64,
    
    #[validate(range(min = 0.0, max = 100000.0))]
    pub velocity: f64,
}

impl IssData {
    pub fn from_json(json: &Value) -> Result<Self, ValidationErrors> {
        let data = Self {
            latitude: json["latitude"].as_f64().unwrap_or(0.0),
            longitude: json["longitude"].as_f64().unwrap_or(0.0),
            altitude: json["altitude"].as_f64().unwrap_or(0.0),
            velocity: json["velocity"].as_f64().unwrap_or(0.0),
        };
        
        data.validate()?;
        Ok(data)
    }
}

#[derive(Debug, Validate)]
pub struct TelemetryData {
    #[validate(range(min = -273.0, max = 1000.0))]
    pub temperature: f64,
    
    #[validate(range(min = 0.0, max = 1000.0))]
    pub voltage: f64,
    
    #[validate(length(min = 1))]
    pub source_file: String,
    
    #[validate(custom = "validate_timestamp")]
    pub recorded_at: DateTime<Utc>,
}

fn validate_timestamp(timestamp: &DateTime<Utc>) -> Result<(), ValidationError> {
    let now = Utc::now();
    let diff = now.signed_duration_since(*timestamp);
    
    // Проверяем что timestamp не в будущем и не старше 1 года
    if diff.num_days() > 365 {
        return Err(ValidationError::new("timestamp_too_old"));
    }
    if diff.num_seconds() < -60 {
        return Err(ValidationError::new("timestamp_in_future"));
    }
    
    Ok(())
}

pub fn validate_iss_payload(payload: &Value) -> HashMap<String, Vec<String>> {
    let mut errors = HashMap::new();
    
    if !payload.is_object() {
        errors.insert("payload".to_string(), vec!["Must be a JSON object".to_string()]);
        return errors;
    }
    
    // Проверяем обязательные поля
    let required_fields = ["latitude", "longitude", "altitude", "velocity"];
    for field in &required_fields {
        if !payload.get(*field).is_some() {
            errors.entry(field.to_string())
                .or_insert_with(Vec::new)
                .push("Field is required".to_string());
        }
    }
    
    // Валидируем числовые поля
    if let Some(lat) = payload["latitude"].as_f64() {
        if lat < -90.0 || lat > 90.0 {
            errors.entry("latitude".to_string())
                .or_insert_with(Vec::new)
                .push("Must be between -90 and 90".to_string());
        }
    }
    
    if let Some(lon) = payload["longitude"].as_f64() {
        if lon < -180.0 || lon > 180.0 {
            errors.entry("longitude".to_string())
                .or_insert_with(Vec::new)
                .push("Must be between -180 and 180".to_string());
        }
    }
    
    errors
}