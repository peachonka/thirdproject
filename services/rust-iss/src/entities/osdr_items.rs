use sea_orm::entity::prelude::*;
use serde::{Deserialize, Serialize};

#[derive(Clone, Debug, PartialEq, DeriveEntityModel, Serialize, Deserialize)]
#[sea_orm(table_name = "osdr_items")]
pub struct Model {
    #[sea_orm(primary_key)]
    pub id: i64,
    #[sea_orm(nullable)]
    pub dataset_id: Option<String>,
    #[sea_orm(nullable)]
    pub title: Option<String>,
    #[sea_orm(nullable)]
    pub status: Option<String>,
    #[sea_orm(nullable)]
    pub updated_at: Option<DateTimeUtc>,
    pub inserted_at: DateTimeUtc,
    #[sea_orm(column_type = "JsonBinary")]
    pub raw: Json,
}

#[derive(Copy, Clone, Debug, EnumIter, DeriveRelation)]
pub enum Relation {}

impl ActiveModelBehavior for ActiveModel {}