// migration/src/m_create_tables.rs
use sea_orm_migration::prelude::*;

pub struct Migration;

impl MigrationName for Migration {
    fn name(&self) -> &str {
        "m_create_tables"
    }
}

#[async_trait::async_trait]
impl MigrationTrait for Migration {
    async fn up(&self, manager: &SchemaManager) -> Result<(), DbErr> {
        // 1. Таблица ISS данных
        manager
            .create_table(
                Table::create()
                    .table(IssFetchLog::Table)
                    .if_not_exists()
                    .col(
                        ColumnDef::new(IssFetchLog::Id)
                            .big_integer()
                            .not_null()
                            .auto_increment()
                            .primary_key(),
                    )
                    .col(
                        ColumnDef::new(IssFetchLog::FetchedAt)
                            .timestamp_with_time_zone()
                            .not_null()
                            .default(Expr::current_timestamp()),
                    )
                    .col(
                        ColumnDef::new(IssFetchLog::SourceUrl)
                            .string()
                            .not_null(),
                    )
                    .col(ColumnDef::new(IssFetchLog::Payload).json_binary().not_null())
                    .to_owned(),
            )
            .await?;

        // 2. Таблица телеметрии легаси
        manager
            .create_table(
                Table::create()
                    .table(TelemetryLegacy::Table)
                    .if_not_exists()
                    .col(
                        ColumnDef::new(TelemetryLegacy::Id)
                            .big_integer()
                            .not_null()
                            .auto_increment()
                            .primary_key(),
                    )
                    .col(
                        ColumnDef::new(TelemetryLegacy::RecordedAt)
                            .string()
                            .not_null(),
                    )
                    .col(ColumnDef::new(TelemetryLegacy::Voltage).double())
                    .col(ColumnDef::new(TelemetryLegacy::Temperature).double())
                    .col(ColumnDef::new(TelemetryLegacy::Pressure).double())
                    .col(ColumnDef::new(TelemetryLegacy::IsOperational).boolean())
                    .col(ColumnDef::new(TelemetryLegacy::ErrorCode).integer())
                    .col(ColumnDef::new(TelemetryLegacy::SourceFile).string())
                    .col(
                        ColumnDef::new(TelemetryLegacy::ImportedAt)
                            .timestamp_with_time_zone()
                            .not_null()
                            .default(Expr::current_timestamp()),
                    )
                    .to_owned(),
            )
            .await?;

        // 3. OSDR items
        manager
            .create_table(
                Table::create()
                    .table(OsdrItems::Table)
                    .if_not_exists()
                    .col(
                        ColumnDef::new(OsdrItems::Id)
                            .big_integer()
                            .not_null()
                            .auto_increment()
                            .primary_key(),
                    )
                    .col(ColumnDef::new(OsdrItems::DatasetId).string().null())
                    .col(ColumnDef::new(OsdrItems::Title).string().null())
                    .col(ColumnDef::new(OsdrItems::Status).string().null())
                    .col(ColumnDef::new(OsdrItems::UpdatedAt).timestamp_with_time_zone().null())
                    .col(
                        ColumnDef::new(OsdrItems::InsertedAt)
                            .timestamp_with_time_zone()
                            .not_null()
                            .default(Expr::current_timestamp()),
                    )
                    .col(ColumnDef::new(OsdrItems::Raw).json_binary().not_null())
                    .to_owned(),
            )
            .await?;

        // Создаём уникальный индекс для dataset_id
        manager
            .create_index(
                Index::create()
                    .name("ux_osdr_dataset_id")
                    .table(OsdrItems::Table)
                    .col(OsdrItems::DatasetId)
                    .if_not_exists()
                    .to_owned(),
            )
            .await?;

        Ok(())
    }

    async fn down(&self, manager: &SchemaManager) -> Result<(), DbErr> {
        manager
            .drop_table(Table::drop().table(IssFetchLog::Table).to_owned())
            .await?;
        manager
            .drop_table(Table::drop().table(TelemetryLegacy::Table).to_owned())
            .await?;
        manager
            .drop_table(Table::drop().table(OsdrItems::Table).to_owned())
            .await?;
        Ok(())
    }
}

// Определения имён таблиц
#[derive(Iden)]
enum IssFetchLog {
    Table,
    Id,
    FetchedAt,
    SourceUrl,
    Payload,
}

#[derive(Iden)]
enum TelemetryLegacy {
    Table,
    Id,
    RecordedAt,
    Voltage,
    Temperature,
    Pressure,
    IsOperational,
    ErrorCode,
    SourceFile,
    ImportedAt,
}

#[derive(Iden)]
enum OsdrItems {
    Table,
    Id,
    DatasetId,
    Title,
    Status,
    UpdatedAt,
    InsertedAt,
    Raw,
}