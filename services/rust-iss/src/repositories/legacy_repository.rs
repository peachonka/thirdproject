use crate::domain::legacy::{TelemetryRecord, LegacyEvent, LegacyStats};
use async_trait::async_trait;
use chrono::{DateTime, Utc, NaiveDateTime};
use sea_orm::{
    ActiveModelTrait, ColumnTrait, DatabaseConnection, EntityTrait, 
    QueryFilter, QueryOrder, QuerySelect, PaginatorTrait, Set, DbErr, ActiveValue
};
use std::sync::Arc;

pub use crate::entities::{telemetry_legacy, legacy_events};

#[async_trait]
pub trait LegacyRepository: Send + Sync {
    async fn save_telemetry_record(&self, record: TelemetryRecord) -> Result<(), DbErr>;
    async fn save_legacy_event(&self, event: LegacyEvent) -> Result<(), DbErr>;
    async fn get_stats(&self) -> Result<LegacyStats, DbErr>;
    async fn get_records_by_file(&self, file_path: &str) -> Result<Vec<TelemetryRecord>, DbErr>;
}

pub struct SeaOrmLegacyRepository {
    db: DatabaseConnection,
}

impl SeaOrmLegacyRepository {
    pub fn new(db: DatabaseConnection) -> Self {
        Self { db }
    }
}

#[async_trait]
impl LegacyRepository for SeaOrmLegacyRepository {
    async fn save_telemetry_record(&self, record: TelemetryRecord) -> Result<(), DbErr> {
    let model = telemetry_legacy::ActiveModel {
        id: ActiveValue::NotSet,
        recorded_at: Set(record.recorded_at), // Уже String
        voltage: Set(Some(record.voltage)),
        temperature: Set(Some(record.temperature)),
        pressure: Set(Some(record.pressure)),
        is_operational: Set(Some(record.is_operational)),
        error_code: Set(Some(record.error_code)),
        source_file: Set(Some(record.source_file)),
        imported_at: Set(Utc::now().into()),
    };

    model.insert(&self.db).await?;
    Ok(())
}

    async fn save_legacy_event(&self, event: LegacyEvent) -> Result<(), DbErr> {
        let model = legacy_events::ActiveModel {
            id: ActiveValue::NotSet,
            file_path: Set(event.file_path),
            action: Set(event.action),
            processed_at: Set(event.processed_at.into()),
        };

        model.insert(&self.db).await?;
        Ok(())
    }

    async fn get_stats(&self) -> Result<LegacyStats, DbErr> {
        // 1. Total records
        let total_records = telemetry_legacy::Entity::find()
            .count(&self.db)
            .await? as i64;

        // 2. Total unique files
        let all_records = telemetry_legacy::Entity::find().all(&self.db).await?;
        let unique_files: std::collections::HashSet<_> = all_records
            .iter()
            .filter_map(|r| r.source_file.as_ref())
            .collect();
        let total_files = unique_files.len() as i64;

        // 3. Last file
        let last_file_result = telemetry_legacy::Entity::find()
            .order_by_desc(telemetry_legacy::Column::RecordedAt)
            .one(&self.db)
            .await?;

        let last_file = last_file_result.and_then(|model| model.source_file);

        // 4. Last import time
        let last_import_result = telemetry_legacy::Entity::find()
            .order_by_desc(telemetry_legacy::Column::ImportedAt)
            .one(&self.db)
            .await?;

        let last_import = last_import_result
            .map(|model| model.imported_at)
            .map(|dt| dt.into());

        Ok(LegacyStats {
            total_files,
            total_records,
            last_file,
            last_import,
        })
    }

    async fn get_records_by_file(&self, file_path: &str) -> Result<Vec<TelemetryRecord>, DbErr> {
    let models = telemetry_legacy::Entity::find()
        .filter(telemetry_legacy::Column::SourceFile.eq(file_path))
        .all(&self.db)
        .await?;

    Ok(models.into_iter().map(|model| TelemetryRecord {
        recorded_at: model.recorded_at, // Уже String, ничего не меняем
        voltage: model.voltage.unwrap_or(0.0),
        temperature: model.temperature.unwrap_or(0.0),
        pressure: model.pressure.unwrap_or(0.0),
        is_operational: model.is_operational.unwrap_or(false),
        error_code: model.error_code.unwrap_or(0),
        source_file: model.source_file.unwrap_or_default(),
    }).collect())
}
}