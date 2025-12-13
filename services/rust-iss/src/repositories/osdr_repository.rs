use sea_orm::DatabaseConnection;

#[derive(Clone)]
pub struct SeaOrmOsdrRepository {
    _db: DatabaseConnection,
}

impl SeaOrmOsdrRepository {
    pub fn new(db: DatabaseConnection) -> Self {
        Self { _db: db }
    }
}

impl crate::services::osdr_service::OsdrRepository for SeaOrmOsdrRepository {}