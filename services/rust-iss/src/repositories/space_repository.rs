use sea_orm::DatabaseConnection;

#[derive(Clone)]
pub struct SeaOrmSpaceRepository {
    _db: DatabaseConnection,
}

impl SeaOrmSpaceRepository {
    pub fn new(db: DatabaseConnection) -> Self {
        Self { _db: db }
    }
}