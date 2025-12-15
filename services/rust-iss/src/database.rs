use sqlx::postgres::PgPoolOptions;
use sqlx::{PgPool, Error};

pub async fn create_pool(database_url: &str) -> Result<PgPool, Error> {
    PgPoolOptions::new()
        .max_connections(5)
        .connect(database_url)
        .await
}