use std::{sync::Arc, time::Duration};

use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    routing::{get, post},
    Json, Router,
};
use chrono::{DateTime, Utc};
use serde::Serialize;
use serde_json::Value;
use tracing::{error, info};
use tracing_subscriber::{EnvFilter, FmtSubscriber};
use tower::ServiceBuilder;

// SeaORM
use sea_orm::{Database, ConnectOptions};

// Импорты tower_http
use tower_http::{
    compression::CompressionLayer,
    timeout::TimeoutLayer,
    trace::TraceLayer,
    cors::CorsLayer,
};

// Наши модули
mod domain;
mod entities;
mod repositories;
mod services;
mod controllers;
mod cache;
mod websocket;

use crate::cache::CacheService;
use crate::websocket::IssUpdate;
use async_broadcast::{broadcast, Sender as IssBroadcaster};

use crate::services::{
    legacy_service::LegacyService,
    osdr_service::OsdrService,
};

use crate::services::iss_service::IssService;
use crate::services::space_service::SpaceService;

use crate::repositories::{
    iss_repository::SeaOrmIssRepository,
    legacy_repository::SeaOrmLegacyRepository,
    space_repository::SeaOrmSpaceRepository,
    osdr_repository::SeaOrmOsdrRepository,
};

use crate::controllers::{
    iss_controller,
    legacy_controller,
    space_controller,
    osdr_controller,
};

#[derive(Serialize)]
struct Health {
    status: &'static str,
    now: DateTime<Utc>,
}

#[derive(Clone)]
struct AppState {
    db: sea_orm::DatabaseConnection,
    iss_service: Arc<IssService>,
    legacy_service: Arc<LegacyService<SeaOrmLegacyRepository>>,
    space_service: Arc<SpaceService>,
    osdr_service: Arc<OsdrService<SeaOrmOsdrRepository>>,
    cache: Arc<CacheService>,
    nasa_url: String,
    nasa_key: String,
    fallback_url: String,
    iss_broadcaster: IssBroadcaster<IssUpdate>,
}

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(EnvFilter::from_default_env())
        .finish();
    let _ = tracing::subscriber::set_global_default(subscriber);

    dotenvy::dotenv().ok();

    let db_url = std::env::var("DATABASE_URL").expect("DATABASE_URL is required");
    let mut options = ConnectOptions::new(db_url);
    options.max_connections(5);
    let db = sea_orm::Database::connect(options).await?;

    let nasa_url = std::env::var("NASA_API_URL").unwrap_or_else(|_| {
        "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json".to_string()
    });
    let nasa_key = std::env::var("NASA_API_KEY").unwrap_or_default();
    let fallback_url = std::env::var("WHERE_ISS_URL")
        .unwrap_or_else(|_| "https://api.wheretheiss.at/v1/satellites/25544".to_string());

    let redis_url = std::env::var("REDIS_URL")
        .unwrap_or_else(|_| "redis://localhost:6379".to_string());
    let cache_service = CacheService::new(&redis_url)
        .map_err(|e| anyhow::anyhow!("Redis connection failed: {}", e))?;
    let cache = Arc::new(cache_service);

    let (iss_tx, _) = broadcast::<IssUpdate>(100);

    let iss_repo = Arc::new(SeaOrmIssRepository::new(db.clone()));
    let legacy_repo = Arc::new(SeaOrmLegacyRepository::new(db.clone()));
    let space_repo = Arc::new(SeaOrmSpaceRepository::new(db.clone()));

    let iss_service = Arc::new(IssService::new(iss_repo, cache.clone()));
    let legacy_service = Arc::new(LegacyService::new(legacy_repo));
    let space_service = Arc::new(SpaceService::new(space_repo, cache.clone()));
    
    let osdr_repo = Arc::new(SeaOrmOsdrRepository::new(db.clone()));
    
    let osdr_service = Arc::new(OsdrService::new(
        osdr_repo,
        nasa_url.clone(),
        nasa_key.clone(),
    ));

    let state = Arc::new(AppState {
        db: db.clone(),
        iss_service: iss_service.clone(),
        legacy_service: legacy_service.clone(),
        space_service: space_service.clone(),
        osdr_service: osdr_service.clone(),
        cache: cache.clone(),
        nasa_url: nasa_url.clone(),
        nasa_key: nasa_key.clone(),
        fallback_url: fallback_url.clone(),
        iss_broadcaster: iss_tx,
    });

    start_all_background_tasks(state.clone());

    let app = Router::new()
        .route("/health", get(|| async {
            Json(Health {
                status: "ok",
                now: Utc::now(),
            })
        }))
        .route("/last", get(iss_controller::last_iss_handler))
        .route("/fetch", get(iss_controller::trigger_iss_handler))
        .route("/iss/trend", get(iss_controller::iss_trend_handler))
        .route("/api/legacy/notify", post(legacy_controller::handle_legacy_notification))
        .route("/api/legacy/stats", get(legacy_controller::get_legacy_stats))
        .route("/api/legacy/export", get(legacy_controller::export_legacy_data))
        .route("/osdr/sync", get(osdr_controller::osdr_sync_handler))
        .route("/osdr/list", get(osdr_controller::osdr_list_handler))
        .route("/space/:src/latest", get(space_controller::space_latest_handler))
        .route("/space/refresh", get(space_controller::space_refresh_handler))
        .route("/space/summary", get(space_controller::space_summary_handler))
        .with_state(state)
        .layer(TraceLayer::new_for_http())
        .layer(CorsLayer::permissive());

    let listener = tokio::net::TcpListener::bind(("0.0.0.0", 3000)).await?;
    info!("rust_iss listening on 0.0.0.0:3000");
    axum::serve(listener, app.into_make_service()).await?;

    Ok(())
}

fn start_all_background_tasks(state: Arc<AppState>) {
    // ISS - каждые 2 минуты
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.iss_service.fetch_and_store(&state.fallback_url).await {
                    error!("ISS background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(120)).await;
            }
        });
    }

    // OSDR - каждые 10 минут
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.osdr_service.fetch_and_store().await {
                    error!("OSDR background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(600)).await;
            }
        });
    }

    // APOD - каждые 12 часов
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.space_service.fetch_apod(&state.nasa_key).await {
                    error!("APOD background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(43200)).await;
            }
        });
    }

    // NEO - каждые 2 часа
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.space_service.fetch_neo_feed(&state.nasa_key).await {
                    error!("NEO background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(7200)).await;
            }
        });
    }

    // DONKI FLR - каждый час
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.space_service.fetch_donki_flr(&state.nasa_key).await {
                    error!("DONKI FLR background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(3600)).await;
            }
        });
    }

    // DONKI CME - каждый час
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.space_service.fetch_donki_cme(&state.nasa_key).await {
                    error!("DONKI CME background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(3600)).await;
            }
        });
    }

    // SpaceX - каждый час
    {
        let state = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = state.space_service.fetch_spacex_next().await {
                    error!("SpaceX background task error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(3600)).await;
            }
        });
    }

    info!("All background tasks started");
}