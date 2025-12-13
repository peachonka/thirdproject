use sea_orm_migration::{prelude::*, schema::*};

#[derive(DeriveMigrationName)]
pub struct Migration;

#[async_trait::async_trait]
impl MigrationTrait for Migration {
    async fn up(&self, manager: &SchemaManager) -> Result<(), DbErr> {
        // 1. Таблица iss_fetch_log
        manager
            .create_table(
                Table::create()
                    .table(IssFetchLog::Table)
                    .if_not_exists()
                    .col(pk_auto(IssFetchLog::Id))
                    .col(timestamp(IssFetchLog::FetchedAt).default(Expr::current_timestamp()))
                    .col(string(IssFetchLog::SourceUrl))
                    .col(json_binary(IssFetchLog::Payload))
                    .to_owned(),
            )
            .await?;

        // 2. Таблица telemetry_legacy
        manager
            .create_table(
                Table::create()
                    .table(TelemetryLegacy::Table)
                    .if_not_exists()
                    .col(pk_auto(TelemetryLegacy::Id))
                    .col(string(TelemetryLegacy::RecordedAt))
                    .col(double(TelemetryLegacy::Voltage).null())
                    .col(double(TelemetryLegacy::Temperature).null())
                    .col(double(TelemetryLegacy::Pressure).null())
                    .col(boolean(TelemetryLegacy::IsOperational).null())
                    .col(integer(TelemetryLegacy::ErrorCode).null())
                    .col(string(TelemetryLegacy::SourceFile).null())
                    .col(timestamp(TelemetryLegacy::ImportedAt).default(Expr::current_timestamp()))
                    .to_owned(),
            )
            .await?;

        // 3. Таблица legacy_events
        manager
            .create_table(
                Table::create()
                    .table(LegacyEvents::Table)
                    .if_not_exists()
                    .col(pk_auto(LegacyEvents::Id))
                    .col(string(LegacyEvents::FilePath))
                    .col(string(LegacyEvents::Action))
                    .col(timestamp(LegacyEvents::ProcessedAt).default(Expr::current_timestamp()))
                    .to_owned(),
            )
            .await?;

        // 4. Таблица osdr_items
        manager
            .create_table(
                Table::create()
                    .table(OsdrItems::Table)
                    .if_not_exists()
                    .col(pk_auto(OsdrItems::Id))
                    .col(string(OsdrItems::DatasetId).null())
                    .col(string(OsdrItems::Title).null())
                    .col(string(OsdrItems::Status).null())
                    .col(timestamp(OsdrItems::UpdatedAt).null())
                    .col(timestamp(OsdrItems::InsertedAt).default(Expr::current_timestamp()))
                    .col(json_binary(OsdrItems::Raw))
                    .to_owned(),
            )
            .await?;

        // 5. Таблица space_cache
        manager
            .create_table(
                Table::create()
                    .table(SpaceCache::Table)
                    .if_not_exists()
                    .col(pk_auto(SpaceCache::Id))
                    .col(string(SpaceCache::Source))
                    .col(timestamp(SpaceCache::FetchedAt).default(Expr::current_timestamp()))
                    .col(json_binary(SpaceCache::Payload))
                    .to_owned(),
            )
            .await?;

        // 6. Таблица cms_pages
        manager
            .create_table(
                Table::create()
                    .table(CmsPages::Table)
                    .if_not_exists()
                    .col(pk_auto(CmsPages::Id))
                    .col(string(CmsPages::Slug).unique_key())
                    .col(string(CmsPages::Title))
                    .col(string(CmsPages::Body))
                    .to_owned(),
            )
            .await?;

        // Индексы
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

        manager
            .create_index(
                Index::create()
                    .name("ix_space_cache_source")
                    .table(SpaceCache::Table)
                    .col(SpaceCache::Source)
                    .col(SpaceCache::FetchedAt)
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
            .drop_table(Table::drop().table(LegacyEvents::Table).to_owned())
            .await?;
        manager
            .drop_table(Table::drop().table(OsdrItems::Table).to_owned())
            .await?;
        manager
            .drop_table(Table::drop().table(SpaceCache::Table).to_owned())
            .await?;
        manager
            .drop_table(Table::drop().table(CmsPages::Table).to_owned())
            .await?;

        Ok(())
    }
}

// Определения имён таблиц и колонок
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
enum LegacyEvents {
    Table,
    Id,
    FilePath,
    Action,
    ProcessedAt,
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

#[derive(Iden)]
enum SpaceCache {
    Table,
    Id,
    Source,
    FetchedAt,
    Payload,
}

#[derive(Iden)]
enum CmsPages {
    Table,
    Id,
    Slug,
    Title,
    Body,
}