use sea_orm::DatabaseConnection;
use std::sync::Arc;
use crate::services::osdr_service::OsdrRepository;

#[derive(Clone)]
pub struct SeaOrmOsdrRepository {
    db: DatabaseConnection,
}

impl SeaOrmOsdrRepository {
    pub fn new(db: DatabaseConnection) -> Self {
        Self { db }
    }
}

// Простая реализация - трейт не требует методов
impl OsdrRepository for SeaOrmOsdrRepository {}