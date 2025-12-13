use crate::domain::legacy::{TelemetryRecord, LegacyEvent, LegacyStats};
use async_trait::async_trait;
use chrono::{DateTime, Utc};
use sea_orm::{
    ActiveModelTrait, ColumnTrait, DatabaseConnection, EntityTrait, 
    QueryFilter, QueryOrder, QuerySelect, PaginatorTrait, Set, DbErr
};
use std::sync::Arc;

// Будем генерировать через sea-orm-cli
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
            recorded_at: Set(record.recorded_at),
            voltage: Set(Some(record.voltage)),
            temperature: Set(Some(record.temperature)),
            pressure: Set(Some(record.pressure)),
            is_operational: Set(Some(record.is_operational)),
            error_code: Set(Some(record.error_code)),
            source_file: Set(Some(record.source_file)),
            ..Default::default()
        };

        model.insert(&self.db).await?;
        Ok(())
    }

    async fn save_legacy_event(&self, event: LegacyEvent) -> Result<(), DbErr> {
        let model = legacy_events::ActiveModel {
            file_path: Set(event.file_path),
            action: Set(event.action),
            processed_at: Set(event.processed_at),
            ..Default::default()
        };

        model.insert(&self.db).await?;
        Ok(())
    }

    async fn get_stats(&self) -> Result<LegacyStats, DbErr> {
        use sea_orm::QueryTrait;
        
        // 1. Total records
        let total_records = telemetry_legacy::Entity::find()
            .count(&self.db)
            .await? as i64;

        // 2. Total unique files
        let total_files = telemetry_legacy::Entity::find()
            .select_only()
            .column_as(
                sea_orm::sea_query::Expr::col(telemetry_legacy::Column::SourceFile).count_distinct().count(),                "count"
            )
            .into_tuple::<i64>()
            .one(&self.db)
            .await?
            .unwrap_or(0);

        // 3. Last file
        let last_file = telemetry_legacy::Entity::find()
            .order_by_desc(telemetry_legacy::Column::RecordedAt)
            .column(telemetry_legacy::Column::SourceFile)
            .one(&self.db)
            .await?
            .and_then(|model| model.source_file);

        // 4. Last import time
        let last_import = telemetry_legacy::Entity::find()
            .select_only()
            .column_as(
                sea_orm::sea_query::Expr::col(telemetry_legacy::Column::ImportedAt).max(),
                "max_imported"
            )
            .into_tuple::<Option<DateTime<Utc>>>()
            .one(&self.db)
            .await?
            .flatten();

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
            recorded_at: model.recorded_at,
            voltage: model.voltage.unwrap_or(0.0),
            temperature: model.temperature.unwrap_or(0.0),
            pressure: model.pressure.unwrap_or(0.0),
            is_operational: model.is_operational.unwrap_or(false),
            error_code: model.error_code.unwrap_or(0),
            source_file: model.source_file.unwrap_or_default(),
        }).collect())
    }
}