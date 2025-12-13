use std::sync::Arc;

pub trait OsdrRepository: Send + Sync {}

pub struct OsdrService<R: OsdrRepository> {
    _repo: Arc<R>,
    _api_url: String,
    _api_key: String,
}

impl<R: OsdrRepository> OsdrService<R> {
    pub fn new(repo: Arc<R>, api_url: String, api_key: String) -> Self {
        Self {
            _repo: repo,
            _api_url: api_url,
            _api_key: api_key,
        }
    }
    
    pub async fn fetch_and_store(&self) -> anyhow::Result<()> {
        Ok(())
    }
}