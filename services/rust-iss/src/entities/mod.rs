pub mod iss_fetch_log;
pub mod telemetry_legacy;
pub mod legacy_events;
pub mod osdr_items;
pub mod space_cache;
pub mod cms_pages;

pub use iss_fetch_log::Entity as IssFetchLog;
pub use telemetry_legacy::Entity as TelemetryLegacy;
pub use legacy_events::Entity as LegacyEvents;
pub use osdr_items::Entity as OsdrItems;
pub use space_cache::Entity as SpaceCache;
pub use cms_pages::Entity as CmsPages;

pub mod prelude {
    pub use super::{
        iss_fetch_log::Model as IssFetchLogModel,
        telemetry_legacy::Model as TelemetryLegacyModel,
        legacy_events::Model as LegacyEventsModel,
        osdr_items::Model as OsdrItemsModel,
        space_cache::Model as SpaceCacheModel,
        cms_pages::Model as CmsPagesModel,
    };
}