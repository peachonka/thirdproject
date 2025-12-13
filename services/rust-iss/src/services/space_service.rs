use std::sync::Arc;

#[derive(Clone)]
pub struct SpaceService {
    // Временно
}

impl SpaceService {
    pub fn new<T, C>(_repo: Arc<T>, _cache: Arc<C>) -> Self {
        Self {}
    }
    
    pub async fn fetch_apod(&self, _key: &str) -> anyhow::Result<()> {
        Ok(())
    }
    
    pub async fn fetch_neo_feed(&self, _key: &str) -> anyhow::Result<()> {
        Ok(())
    }
    
    pub async fn fetch_donki_flr(&self, _key: &str) -> anyhow::Result<()> {
        Ok(())
    }
    
    pub async fn fetch_donki_cme(&self, _key: &str) -> anyhow::Result<()> {
        Ok(())
    }
    
    pub async fn fetch_spacex_next(&self) -> anyhow::Result<()> {
        Ok(())
    }
}

pub enum SpaceSource {
    Apod,
    Neo,
    Flr,
    Cme,
    Spacex,
}