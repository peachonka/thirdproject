use sea_orm::entity::prelude::*;
use serde::{Deserialize, Serialize};

#[derive(Clone, Debug, PartialEq, DeriveEntityModel, Serialize, Deserialize)]
#[sea_orm(table_name = "telemetry_legacy")]
pub struct Model {
    #[sea_orm(primary_key)]
    pub id: i64,
    pub recorded_at: String,
    #[sea_orm(nullable)]
    pub voltage: Option<f64>,
    #[sea_orm(nullable)]
    pub temperature: Option<f64>,
    #[sea_orm(nullable)]
    pub pressure: Option<f64>,
    #[sea_orm(nullable)]
    pub is_operational: Option<bool>,
    #[sea_orm(nullable)]
    pub error_code: Option<i32>,
    #[sea_orm(nullable)]
    pub source_file: Option<String>,
    pub imported_at: DateTimeUtc,
}

#[derive(Copy, Clone, Debug, EnumIter, DeriveRelation)]
pub enum Relation {}

impl ActiveModelBehavior for ActiveModel {}