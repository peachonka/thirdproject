use redis::{Client, RedisResult};
use serde_json::Value;

#[derive(Clone)]
pub struct CacheService {
    client: Client,
}

impl CacheService {
    pub fn new(redis_url: &str) -> RedisResult<Self> {
        let client = Client::open(redis_url)?;
        Ok(Self { client })
    }

    pub async fn get_json(&self, key: &str) -> RedisResult<Option<Value>> {
        let mut conn = self.client.get_multiplexed_async_connection().await?;
        let data: Option<String> = redis::cmd("GET")
            .arg(key)
            .query_async(&mut conn)
            .await?;
        
        match data {
            Some(json_str) => {
                let value: Value = serde_json::from_str(&json_str)
                    .map_err(|e| redis::RedisError::from(std::io::Error::new(
                        std::io::ErrorKind::InvalidData, 
                        format!("JSON parse error: {}", e)
                    )))?;
                Ok(Some(value))
            }
            None => Ok(None),
        }
    }

    pub async fn set_json(&self, key: &str, value: &Value, ttl_secs: usize) -> RedisResult<()> {
        let mut conn = self.client.get_multiplexed_async_connection().await?;
        let json_str = serde_json::to_string(value)
            .map_err(|e| redis::RedisError::from(std::io::Error::new(
                std::io::ErrorKind::InvalidData,
                format!("JSON serialize error: {}", e)
            )))?;
        
        redis::cmd("SET")
            .arg(key)
            .arg(&json_str)
            .arg("EX")
            .arg(ttl_secs)
            .query_async::<_, ()>(&mut conn)  // Явно указываем тип
            .await?;
        
        Ok(())
    }
}