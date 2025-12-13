use axum::{
    extract::Request,
    middleware::Next,
    response::Response,
    http::{StatusCode, header},
};
use std::sync::Arc;
use crate::AppState;

pub async fn rate_limit_middleware(
    State(state): State<Arc<AppState>>,
    request: Request,
    next: Next,
) -> Result<Response, (StatusCode, String)> {
    let ip = request.headers()
        .get("x-forwarded-for")
        .or_else(|| request.headers().get("x-real-ip"))
        .and_then(|h| h.to_str().ok())
        .unwrap_or("unknown");
    
    let endpoint = request.uri().path().to_string();
    let rate_limit_key = format!("rate_limit:{}:{}", ip, endpoint);
    
    // 100 запросов в минуту на endpoint для IP
    let requests_in_window = state.cache.increment_rate_limit(&rate_limit_key, 60)
        .await
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))?;
    
    if requests_in_window > 100 {
        return Err((StatusCode::TOO_MANY_REQUESTS, 
            "Rate limit exceeded. Maximum 100 requests per minute.".to_string()));
    }
    
    // Добавляем заголовки с информацией о rate limit
    let mut response = next.run(request).await;
    response.headers_mut().insert(
        "X-RateLimit-Limit",
        header::HeaderValue::from_static("100")
    );
    response.headers_mut().insert(
        "X-RateLimit-Remaining",
        header::HeaderValue::from((100 - requests_in_window).to_string())
    );
    
    Ok(response)
}