use async_trait::async_trait;
use sea_orm::{
    ActiveModelTrait, DatabaseConnection, EntityTrait,
    QueryOrder, Set, DbErr, Value
};
use serde_json::Value;
use crate::entities::iss_fetch_log;
use sea_orm::QuerySelect;

#[async_trait]
pub trait IssRepository: Send + Sync {
    async fn save_fetch(&self, source_url: &str, payload: Value) -> Result<i64, DbErr>;
    async fn get_latest(&self) -> Result<Option<iss_fetch_log::Model>, DbErr>;
    async fn get_last_n(&self, n: u64) -> Result<Vec<iss_fetch_log::Model>, DbErr>;
}

pub struct SeaOrmIssRepository {
    db: DatabaseConnection,
}

impl SeaOrmIssRepository {
    pub fn new(db: DatabaseConnection) -> Self {
        Self { db }
    }
}

#[async_trait]
impl IssRepository for SeaOrmIssRepository {
    async fn save_fetch(&self, source_url: &str, payload: Value) -> Result<i64, DbErr> {
        let model = iss_fetch_log::ActiveModel {
            source_url: Set(source_url.to_string()),
            payload: Set(Value::Json(Some(serde_json::to_value(payload).unwrap())))
            ..Default::default()
        };

        let result = model.insert(&self.db).await?;
        Ok(result.id)
    }

    async fn get_latest(&self) -> Result<Option<iss_fetch_log::Model>, DbErr> {
        iss_fetch_log::Entity::find()
            .order_by_desc(iss_fetch_log::Column::Id)
            .one(&self.db)
            .await
    }

    async fn get_last_n(&self, n: u64) -> Result<Vec<iss_fetch_log::Model>, DbErr> {
        iss_fetch_log::Entity::find()
            .order_by_desc(iss_fetch_log::Column::Id)
            .limit(n)
            .all(&self.db)
            .await
    }
}