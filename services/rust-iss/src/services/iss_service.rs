use std::sync::Arc;

#[derive(Clone)]
pub struct IssService {
    // Пока пусто, но принимаем параметры
}

impl IssService {
    pub fn new<T, C>(_repo: Arc<T>, _cache: Arc<C>) -> Self {
        Self {}
    }
    
    pub async fn fetch_and_store(&self, _url: &str) -> anyhow::Result<()> {
        Ok(())
    }
    
    pub async fn get_latest(&self) -> anyhow::Result<Option<()>> {
        Ok(None)
    }
    
    pub async fn get_trend(&self) -> anyhow::Result<Option<TrendAnalysis>> {
        Ok(None)
    }
}

#[derive(Debug)]
pub struct TrendAnalysis {
    pub movement: bool,
    pub delta_km: f64,
    pub velocity_kmh: f64,
}