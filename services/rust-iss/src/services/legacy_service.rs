use std::sync::Arc;

#[derive(Clone)]
pub struct LegacyService<T> {
    _repo: Arc<T>,
}

impl<T> LegacyService<T> {
    pub fn new(repo: Arc<T>) -> Self {
        Self { _repo: repo }
    }
}