@extends('layouts.app')

@section('title', 'Космический дашборд')

@push('styles')
<style>
    /* Стили для виджетов */
    .widget {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(108, 99, 255, 0.2);
        border-radius: 15px;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .widget:hover {
        transform: translateY(-5px);
        border-color: #6c63ff;
        box-shadow: 0 10px 25px rgba(108, 99, 255, 0.2);
    }
    
    .widget-header {
        border-bottom: 1px solid rgba(108, 99, 255, 0.3);
        padding: 15px 20px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 15px 15px 0 0;
    }
    
    .widget-title {
        color: #6c63ff;
        margin: 0;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .widget-actions {
        display: flex;
        gap: 5px;
    }
    
    .widget-body {
        padding: 20px;
    }
    
    /* Сетка дашборда */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    /* Анимация загрузки виджетов */
    .widget-loading {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px;
    }
    
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(108, 99, 255, 0.2);
        border-top: 4px solid #6c63ff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Статистические карточки */
    .stat-card {
        background: linear-gradient(135deg, rgba(108, 99, 255, 0.1) 0%, rgba(0, 180, 216, 0.1) 100%);
        border: 1px solid rgba(108, 99, 255, 0.3);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: scale(1.05);
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #6c63ff;
        margin: 10px 0;
    }
    
    .stat-label {
        color: #a0a0c0;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    /* Фильтры дашборда */
    .dashboard-filters {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid rgba(108, 99, 255, 0.2);
    }
    
    .filter-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .filter-tag {
        background: rgba(108, 99, 255, 0.2);
        border: 1px solid #6c63ff;
        border-radius: 20px;
        padding: 5px 15px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .widget {
            margin-bottom: 20px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <!-- Заголовок и фильтры -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="dashboard-filters">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Космический дашборд
                    </h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" id="resetFilters">
                            <i class="fas fa-redo me-1"></i> Сбросить фильтры
                        </button>
                        <button class="btn btn-sm btn-primary" id="saveLayout">
                            <i class="fas fa-save me-1"></i> Сохранить расположение
                        </button>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Период времени</label>
                        <select class="form-select form-select-sm" id="timeRange">
                            <option value="24h">Последние 24 часа</option>
                            <option value="7d" selected>Последние 7 дней</option>
                            <option value="30d">Последние 30 дней</option>
                            <option value="90d">Последние 90 дней</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Источники данных</label>
                        <select class="form-select form-select-sm" id="dataSources" multiple>
                            <option value="iss" selected>МКС</option>
                            <option value="jwst" selected>JWST</option>
                            <option value="neo" selected>Астероиды</option>
                            <option value="apod" selected>APOD</option>
                            <option value="spacex" selected>SpaceX</option>
                            <option value="osdr" selected>OSDR</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Тип виджетов</label>
                        <select class="form-select form-select-sm" id="widgetTypes">
                            <option value="all" selected>Все типы</option>
                            <option value="charts">Графики</option>
                            <option value="tables">Таблицы</option>
                            <option value="maps">Карты</option>
                            <option value="stats">Статистика</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Сортировка</label>
                        <select class="form-select form-select-sm" id="sortBy">
                            <option value="custom">Мое расположение</option>
                            <option value="name">По названию</option>
                            <option value="importance">По важности</option>
                            <option value="update">По времени обновления</option>
                        </select>
                    </div>
                </div>
                
                <!-- Активные фильтры -->
                <div class="filter-tags mt-3" id="activeFilters">
                    <!-- Фильтры будут добавляться динамически -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Статистические карточки -->
    <div class="row mb-4 fade-in">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">Скорость МКС</div>
                <div class="stat-value" id="statIssSpeed">
                    {{ isset(($iss['payload'] ?? [])['velocity']) ? number_format($iss['payload']['velocity'],0,'',' ') : '—' }}
                </div>
                <small class="text-muted">км/ч</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">Высота МКС</div>
                <div class="stat-value" id="statIssAltitude">
                    {{ isset(($iss['payload'] ?? [])['altitude']) ? number_format($iss['payload']['altitude'],0,'',' ') : '—' }}
                </div>
                <small class="text-muted">км</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">Астероиды</div>
                <div class="stat-value" id="statNeoCount">0</div>
                <small class="text-muted">за 7 дней</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">Опасные</div>
                <div class="stat-value" id="statHazardousCount">0</div>
                <small class="text-muted">астероиды</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">JWST</div>
                <div class="stat-value" id="statJwstCount">0</div>
                <small class="text-muted">изображений</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">SpaceX</div>
                <div class="stat-value" id="statSpacexCount">0</div>
                <small class="text-muted">предстоящие</small>
            </div>
        </div>
    </div>
    
    <!-- Основная сетка виджетов -->
    <div class="dashboard-grid" id="dashboardWidgets">
        <!-- Виджет ISS - карта и позиция -->
        <div class="widget" data-widget-id="iss-map" data-source="iss" data-type="maps">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-satellite"></i> Позиция МКС
                </h5>
                <div class="widget-actions">
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshWidget('iss-map')">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleFullscreen('iss-map')">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            <div class="widget-body">
                <div id="issMapWidget" style="height: 300px;"></div>
                <div class="row mt-3">
                    <div class="col-6">
                        <small class="text-muted">Широта</small>
                        <div id="issLatitude">{{ $iss['payload']['latitude'] ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Долгота</small>
                        <div id="issLongitude">{{ $iss['payload']['longitude'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Виджет ISS - графики скорости и высоты -->
        <div class="widget" data-widget-id="iss-charts" data-source="iss" data-type="charts">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-chart-line"></i> Динамика МКС
                </h5>
                <div class="widget-actions">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="changeChartType('speed', 'line')">Линейный график</a></li>
                        <li><a class="dropdown-item" href="#" onclick="changeChartType('speed', 'bar')">Столбчатая диаграмма</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="exportChartData('speed')">Экспорт данных</a></li>
                    </ul>
                </div>
            </div>
            <div class="widget-body">
                <div class="row">
                    <div class="col-6">
                        <canvas id="issSpeedChartWidget" height="150"></canvas>
                        <div class="text-center small text-muted mt-2">Скорость (км/ч)</div>
                    </div>
                    <div class="col-6">
                        <canvas id="issAltitudeChartWidget" height="150"></canvas>
                        <div class="text-center small text-muted mt-2">Высота (км)</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Виджет JWST - галерея -->
        <div class="widget" data-widget-id="jwst-gallery" data-source="jwst" data-type="images">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-telescope"></i> JWST Галерея
                </h5>
                <div class="widget-actions">
                    <select class="form-select form-select-sm" style="width: auto;" onchange="filterJwstGallery(this.value)">
                        <option value="latest">Последние</option>
                        <option value="popular">Популярные</option>
                        <option value="nircam">NIRCam</option>
                        <option value="miri">MIRI</option>
                    </select>
                </div>
            </div>
            <div class="widget-body">
                <div id="jwstGalleryWidget" class="row g-2">
                    <!-- Изображения будут загружены через JS -->
                    <div class="col-12 text-center">
                        <div class="widget-loading">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-sm btn-outline-primary" onclick="loadMoreJwst()">
                        <i class="fas fa-plus me-1"></i> Загрузить еще
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Виджет астероидов -->
        <div class="widget" data-widget-id="neo-table" data-source="neo" data-type="tables">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-meteor"></i> Ближайшие астероиды
                </h5>
                <div class="widget-actions">
                    <button class="btn btn-sm btn-outline-danger" onclick="showOnlyHazardous()">
                        <i class="fas fa-exclamation-triangle"></i> Только опасные
                    </button>
                </div>
            </div>
            <div class="widget-body">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover" id="neoTableWidget">
                        <thead>
                            <tr>
                                <th>Имя</th>
                                <th>Размер</th>
                                <th>Скорость</th>
                                <th>Опасность</th>
                            </tr>
                        </thead>
                        <tbody id="neoTableBody">
                            <!-- Данные будут загружены через JS -->
                            <tr>
                                <td colspan="4" class="text-center">
                                    <div class="widget-loading">
                                        <div class="loading-spinner"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Показано: <span id="neoCount">0</span> астероидов</small>
                </div>
            </div>
        </div>
        
        <!-- Виджет APOD -->
        <div class="widget" data-widget-id="apod-widget" data-source="apod" data-type="images">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-image"></i> Картина дня (APOD)
                </h5>
                <div class="widget-actions">
                    <button class="btn btn-sm btn-outline-secondary" onclick="showRandomApod()">
                        <i class="fas fa-random"></i> Случайная
                    </button>
                </div>
            </div>
            <div class="widget-body">
                <div id="apodWidget">
                    <div class="text-center">
                        <div class="widget-loading">
                            <div class="loading-spinner"></div>
                        </div>
                        <p class="text-muted mt-2">Загрузка картины дня...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Виджет SpaceX -->
        <div class="widget" data-widget-id="spacex-next" data-source="spacex" data-type="stats">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-rocket"></i> Следующий запуск SpaceX
                </h5>
                <div class="widget-actions">
                    <button class="btn btn-sm btn-outline-warning" onclick="refreshSpaceX()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="widget-body">
                <div id="spacexWidget">
                    <div class="text-center">
                        <div class="widget-loading">
                            <div class="loading-spinner"></div>
                        </div>
                        <p class="text-muted mt-2">Загрузка данных о запуске...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Виджет OSDR -->
        <div class="widget" data-widget-id="osdr-stats" data-source="osdr" data-type="stats">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-database"></i> Статистика OSDR
                </h5>
                <div class="widget-actions">
                    <button class="btn btn-sm btn-outline-info" onclick="exportOsdrStats()">
                        <i class="fas fa-download"></i> Экспорт
                    </button>
                </div>
            </div>
            <div class="widget-body">
                <div id="osdrWidget">
                    <div class="text-center">
                        <div class="widget-loading">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Виджет астрономических событий -->
        <div class="widget" data-widget-id="astro-events" data-source="astro" data-type="tables">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="widget-title">
                    <i class="fas fa-star"></i> Астрономические события
                </h5>
                <div class="widget-actions">
                    <input type="number" class="form-control form-control-sm" 
                           style="width: 80px;" 
                           placeholder="Дней" 
                           value="7" 
                           id="astroDays"
                           onchange="updateAstroEvents()">
                </div>
            </div>
            <div class="widget-body">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm" id="astroTableWidget">
                        <thead>
                            <tr>
                                <th>Событие</th>
                                <th>Дата</th>
                                <th>Тип</th>
                            </tr>
                        </thead>
                        <tbody id="astroTableBody">
                            <tr>
                                <td colspan="3" class="text-center">
                                    <div class="widget-loading">
                                        <div class="loading-spinner"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Панель управления виджетами -->
    <div class="row mt-4 fade-in">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-sliders-h me-2"></i>Управление виджетами
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                <label class="form-check-label" for="autoRefresh">
                                    Автообновление (каждые 60 сек)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notifications" checked>
                                <label class="form-check-label" for="notifications">
                                    Уведомления о новых данных
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="darkMode" checked>
                                <label class="form-check-label" for="darkMode">
                                    Темная тема
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Плотность виджетов</label>
                            <select class="form-select form-select-sm" id="widgetDensity">
                                <option value="compact">Компактная</option>
                                <option value="normal" selected>Нормальная</option>
                                <option value="spacious">Просторная</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Обновление данных</label>
                            <select class="form-select form-select-sm" id="refreshInterval">
                                <option value="30">Каждые 30 сек</option>
                                <option value="60" selected>Каждые 60 сек</option>
                                <option value="300">Каждые 5 мин</option>
                                <option value="900">Каждые 15 мин</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Глобальные переменные
let dashboardWidgets = [];
let autoRefreshInterval;
let currentFilters = {
    timeRange: '7d',
    dataSources: ['iss', 'jwst', 'neo', 'apod', 'spacex', 'osdr'],
    widgetTypes: 'all',
    sortBy: 'custom'
};

// Инициализация дашборда
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
    loadWidgetData();
    setupEventListeners();
    startAutoRefresh();
});

// Инициализация дашборда
function initDashboard() {
    // Сделать виджеты перетаскиваемыми
    const dashboardGrid = document.getElementById('dashboardWidgets');
    if (dashboardGrid) {
        Sortable.create(dashboardGrid, {
            animation: 150,
            ghostClass: 'widget-ghost',
            onEnd: function(evt) {
                saveWidgetLayout();
            }
        });
    }
    
    // Восстановить сохраненный layout
    restoreWidgetLayout();
    
    // Инициализация карты ISS
    initIssMap();
    
    // Загрузка статистики
    updateDashboardStats();
}

// Загрузка данных виджетов
function loadWidgetData() {
    // Загрузка данных ISS
    loadIssData();
    
    // Загрузка галереи JWST
    loadJwstGallery();
    
    // Загрузка данных астероидов
    loadNeoData();
    
    // Загрузка APOD
    loadApod();
    
    // Загрузка данных SpaceX
    loadSpaceXData();
    
    // Загрузка данных OSDR
    loadOsdrData();
    
    // Загрузка астрономических событий
    loadAstroEvents();
}

// Настройка обработчиков событий
function setupEventListeners() {
    // Фильтры
    document.getElementById('timeRange').addEventListener('change', function() {
        currentFilters.timeRange = this.value;
        updateActiveFilters();
        refreshDashboard();
    });
    
    document.getElementById('dataSources').addEventListener('change', function() {
        const selected = Array.from(this.selectedOptions).map(opt => opt.value);
        currentFilters.dataSources = selected;
        updateActiveFilters();
        filterWidgetsBySource();
    });
    
    document.getElementById('widgetTypes').addEventListener('change', function() {
        currentFilters.widgetTypes = this.value;
        updateActiveFilters();
        filterWidgetsByType();
    });
    
    document.getElementById('sortBy').addEventListener('change', function() {
        currentFilters.sortBy = this.value;
        sortWidgets();
    });
    
    // Кнопки управления
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('saveLayout').addEventListener('click', saveWidgetLayout);
    
    // Настройки
    document.getElementById('autoRefresh').addEventListener('change', toggleAutoRefresh);
    document.getElementById('refreshInterval').addEventListener('change', updateRefreshInterval);
    document.getElementById('widgetDensity').addEventListener('change', updateWidgetDensity);
}

// ====== ФУНКЦИИ ДЛЯ КОНКРЕТНЫХ ВИДЖЕТОВ ======

// ISS: Инициализация карты
function initIssMap() {
    const mapContainer = document.getElementById('issMapWidget');
    if (!mapContainer) return;
    
    const last = @json(($iss['payload'] ?? []));
    let lat0 = Number(last.latitude || 0);
    let lon0 = Number(last.longitude || 0);
    
    const map = L.map(mapContainer).setView([lat0 || 0, lon0 || 0], lat0 ? 3 : 2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', {
        noWrap: true,
        attribution: ''
    }).addTo(map);
    
    const marker = L.marker([lat0 || 0, lon0 || 0]).addTo(map)
        .bindPopup('Международная космическая станция');
    
    // Сохраняем карту для обновления
    window.issMap = map;
    window.issMarker = marker;
}

// ISS: Загрузка данных
function loadIssData() {
    fetch('/api/iss/trend?limit=50')
        .then(response => response.json())
        .then(data => {
            updateIssCharts(data);
            updateIssPosition(data);
            updateIssStats(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки данных ISS:', error);
        });
}

// ISS: Обновление графиков
function updateIssCharts(data) {
    const points = data.points || [];
    
    // График скорости
    const speedCtx = document.getElementById('issSpeedChartWidget')?.getContext('2d');
    if (speedCtx && window.issSpeedChart) {
        window.issSpeedChart.destroy();
    }
    
    if (speedCtx) {
        const labels = points.map(p => new Date(p.at).toLocaleTimeString());
        const speeds = points.map(p => p.velocity || 0);
        
        window.issSpeedChart = new Chart(speedCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Скорость',
                    data: speeds,
                    borderColor: '#6c63ff',
                    backgroundColor: 'rgba(108, 99, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0c0'
                        }
                    }
                }
            }
        });
    }
    
    // График высоты
    const altCtx = document.getElementById('issAltitudeChartWidget')?.getContext('2d');
    if (altCtx && window.issAltChart) {
        window.issAltChart.destroy();
    }
    
    if (altCtx) {
        const labels = points.map(p => new Date(p.at).toLocaleTimeString());
        const altitudes = points.map(p => p.altitude || 0);
        
        window.issAltChart = new Chart(altCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Высота',
                    data: altitudes,
                    borderColor: '#00b4d8',
                    backgroundColor: 'rgba(0, 180, 216, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0c0'
                        }
                    }
                }
            }
        });
    }
}

// ISS: Обновление позиции на карте
function updateIssPosition(data) {
    const points = data.points || [];
    if (points.length === 0 || !window.issMap || !window.issMarker) return;
    
    const lastPoint = points[points.length - 1];
    const lat = lastPoint.lat || 0;
    const lon = lastPoint.lon || 0;
    
    // Обновляем маркер
    window.issMarker.setLatLng([lat, lon]);
    
    // Обновляем координаты в виджете
    document.getElementById('issLatitude').textContent = lat.toFixed(4);
    document.getElementById('issLongitude').textContent = lon.toFixed(4);
    
    // Обновляем трек если нужно
    if (window.issTrack) {
        const trailPoints = points.map(p => [p.lat, p.lon]);
        window.issTrack.setLatLngs(trailPoints);
    }
}

// JWST: Загрузка галереи
function loadJwstGallery(filter = 'latest') {
    const galleryContainer = document.getElementById('jwstGalleryWidget');
    if (!galleryContainer) return;
    
    galleryContainer.innerHTML = `
        <div class="col-12 text-center">
            <div class="widget-loading">
                <div class="loading-spinner"></div>
            </div>
        </div>
    `;
    
    fetch(`/api/jwst/feed?source=jpg&perPage=6`)
        .then(response => response.json())
        .then(data => {
            const items = data.items || [];
            updateJwstGallery(items);
            updateJwstStats(items.length);
        })
        .catch(error => {
            console.error('Ошибка загрузки галереи JWST:', error);
            galleryContainer.innerHTML = `
                <div class="col-12 text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки
                </div>
            `;
        });
}

// JWST: Обновление галереи
function updateJwstGallery(items) {
    const galleryContainer = document.getElementById('jwstGalleryWidget');
    if (!galleryContainer) return;
    
    if (items.length === 0) {
        galleryContainer.innerHTML = `
            <div class="col-12 text-center text-muted">
                <i class="fas fa-image"></i> Нет изображений
            </div>
        `;
        return;
    }
    
    let html = '';
    items.forEach((item, index) => {
        if (index >= 6) return; // Ограничиваем 6 изображениями
        
        html += `
            <div class="col-4">
                <div class="card bg-dark border-0">
                    <a href="${item.link || item.url}" target="_blank" rel="noreferrer">
                        <img src="${item.url}" 
                             class="card-img-top" 
                             alt="JWST Image"
                             style="height: 100px; object-fit: cover; border-radius: 5px;">
                    </a>
                    <div class="card-body p-2">
                        <small class="text-muted d-block" style="font-size: 0.7rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${item.caption || 'JWST Image'}
                        </small>
                    </div>
                </div>
            </div>
        `;
    });
    
    galleryContainer.innerHTML = html;
}

// NEO: Загрузка данных астероидов
function loadNeoData() {
    const tableBody = document.getElementById('neoTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = `
        <tr>
            <td colspan="4" class="text-center">
                <div class="widget-loading">
                    <div class="loading-spinner"></div>
                </div>
            </td>
        </tr>
    `;
    
    fetch('/api/neo/feed')
        .then(response => response.json())
        .then(data => {
            updateNeoTable(data);
            updateNeoStats(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки данных NEO:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки
                    </td>
                </tr>
            `;
        });
}

// NEO: Обновление таблицы
function updateNeoTable(data) {
    const tableBody = document.getElementById('neoTableBody');
    const countElement = document.getElementById('neoCount');
    
    if (!tableBody || !countElement) return;
    
    const items = data.items || data.asteroids || [];
    let hazardousCount = 0;
    
    if (items.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-meteor"></i> Нет данных об астероидах
                </td>
            </tr>
        `;
        countElement.textContent = '0';
        return;
    }
    
    let html = '';
    items.slice(0, 10).forEach(item => {
        const name = item.name || 'Неизвестно';
        const diameter = item.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
        const velocity = item.close_approach_data?.[0]?.relative_velocity?.kilometers_per_hour || 0;
        const hazardous = item.is_potentially_hazardous_asteroid || false;
        
        if (hazardous) hazardousCount++;
        
        html += `
            <tr>
                <td>
                    <span class="d-inline-block text-truncate" style="max-width: 150px;">
                        ${name}
                    </span>
                </td>
                <td>${diameter.toFixed(3)} км</td>
                <td>${parseInt(velocity).toLocaleString()} км/ч</td>
                <td>
                    ${hazardous ? 
                        '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Опасен</span>' : 
                        '<span class="badge bg-success"><i class="fas fa-check"></i> Безопасен</span>'}
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    countElement.textContent = items.length;
    
    // Обновляем статистику опасных
    document.getElementById('statHazardousCount').textContent = hazardousCount;
}

// APOD: Загрузка картины дня
function loadApod() {
    const apodContainer = document.getElementById('apodWidget');
    if (!apodContainer) return;
    
    fetch('/api/apod/latest')
        .then(response => response.json())
        .then(data => {
            updateApodWidget(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки APOD:', error);
            apodContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки APOD
                </div>
            `;
        });
}

// APOD: Обновление виджета
function updateApodWidget(data) {
    const apodContainer = document.getElementById('apodWidget');
    if (!apodContainer) return;
    
    const apod = data.payload || data;
    
    if (!apod.url) {
        apodContainer.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-image"></i> Нет данных
            </div>
        `;
        return;
    }
    
    const html = `
        <div class="card bg-dark border-0">
            <a href="${apod.hdurl || apod.url}" target="_blank" rel="noreferrer">
                <img src="${apod.url}" 
                     class="card-img-top" 
                     alt="${apod.title || 'APOD'}"
                     style="height: 200px; object-fit: cover; border-radius: 10px;">
            </a>
            <div class="card-body p-3">
                <h6 class="card-title">${apod.title || 'Astronomy Picture of the Day'}</h6>
                <p class="card-text small text-muted" style="font-size: 0.8rem;">
                    ${(apod.explanation || '').substring(0, 100)}...
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">${apod.date || ''}</small>
                    <small class="text-muted">${apod.copyright || 'NASA'}</small>
                </div>
            </div>
        </div>
    `;
    
    apodContainer.innerHTML = html;
}

// SpaceX: Загрузка данных
function loadSpaceXData() {
    const spacexContainer = document.getElementById('spacexWidget');
    if (!spacexContainer) return;
    
    fetch('/api/spacex/next')
        .then(response => response.json())
        .then(data => {
            updateSpaceXWidget(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки SpaceX:', error);
            spacexContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки
                </div>
            `;
        });
}

// SpaceX: Обновление виджета
function updateSpaceXWidget(data) {
    const spacexContainer = document.getElementById('spacexWidget');
    if (!spacexContainer) return;
    
    const launch = data.payload || data;
    
    if (!launch.name) {
        spacexContainer.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-rocket"></i> Нет данных о предстоящих запусках
            </div>
        `;
        return;
    }
    
    const launchDate = launch.date_utc ? new Date(launch.date_utc) : null;
    const now = new Date();
    const timeDiff = launchDate ? launchDate.getTime() - now.getTime() : 0;
    const daysUntil = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    const html = `
        <div class="text-center">
            <h5 class="mb-3">${launch.name || 'Предстоящий запуск'}</h5>
            ${launchDate ? `
                <div class="mb-3">
                    <div class="display-6 text-warning">${daysUntil}</div>
                    <small class="text-muted">дней до запуска</small>
                </div>
                <div class="small text-muted mb-3">
                    ${launchDate.toLocaleDateString()} ${launchDate.toLocaleTimeString()}
                </div>
            ` : ''}
            ${launch.details ? `
                <p class="small text-muted" style="font-size: 0.8rem;">
                    ${launch.details.substring(0, 100)}...
                </p>
            ` : ''}
            ${launch.links?.webcast ? `
                <a href="${launch.links.webcast}" 
                   target="_blank" 
                   class="btn btn-sm btn-danger">
                    <i class="fab fa-youtube"></i> Смотреть трансляцию
                </a>
            ` : ''}
        </div>
    `;
    
    spacexContainer.innerHTML = html;
    
    // Обновляем статистику
    document.getElementById('statSpacexCount').textContent = daysUntil > 0 ? daysUntil : '0';
}

// OSDR: Загрузка данных
function loadOsdrData() {
    const osdrContainer = document.getElementById('osdrWidget');
    if (!osdrContainer) return;
    
    fetch('/api/osdr/list?limit=5')
        .then(response => response.json())
        .then(data => {
            updateOsdrWidget(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки OSDR:', error);
            osdrContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки
                </div>
            `;
        });
}

// OSDR: Обновление виджета
function updateOsdrWidget(data) {
    const osdrContainer = document.getElementById('osdrWidget');
    if (!osdrContainer) return;
    
    const items = data.items || [];
    const total = items.length;
    
    if (total === 0) {
        osdrContainer.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-database"></i> Нет данных OSDR
            </div>
        `;
        return;
    }
    
    // Группируем по статусу
    const statusCounts = {};
    items.forEach(item => {
        const status = item.status || 'unknown';
        statusCounts[status] = (statusCounts[status] || 0) + 1;
    });
    
    let statusHtml = '';
    for (const [status, count] of Object.entries(statusCounts)) {
        const percentage = ((count / total) * 100).toFixed(1);
        statusHtml += `
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span class="small">${status}</span>
                    <span class="small">${percentage}%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar" 
                         role="progressbar" 
                         style="width: ${percentage}%">
                    </div>
                </div>
            </div>
        `;
    }
    
    const html = `
        <div>
            <div class="text-center mb-3">
                <h3 class="text-info">${total}</h3>
                <small class="text-muted">датасетов OSDR</small>
            </div>
            <div class="mt-3">
                ${statusHtml}
            </div>
        </div>
    `;
    
    osdrContainer.innerHTML = html;
}

// Астрономические события: Загрузка
function loadAstroEvents(days = 7) {
    const tableBody = document.getElementById('astroTableBody');
    if (!tableBody) return;
    
    fetch(`/api/astro/events?days=${days}`)
        .then(response => response.json())
        .then(data => {
            updateAstroTable(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки астрономических событий:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки
                    </td>
                </tr>
            `;
        });
}

// Астрономические события: Обновление таблицы
function updateAstroTable(data) {
    const tableBody = document.getElementById('astroTableBody');
    if (!tableBody) return;
    
    // Извлекаем события из сложной структуры ответа
    const events = extractAstroEvents(data);
    
    if (events.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted">
                    <i class="fas fa-star"></i> Нет событий на выбранный период
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    events.slice(0, 10).forEach(event => {
        html += `
            <tr>
                <td>
                    <span class="d-inline-block text-truncate" style="max-width: 150px;">
                        ${event.name || 'Неизвестно'}
                    </span>
                </td>
                <td>
                    <small>${event.when ? new Date(event.when).toLocaleDateString() : '—'}</small>
                </td>
                <td>
                    <span class="badge bg-info">${event.type || 'Событие'}</span>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

// Вспомогательная функция для извлечения событий
function extractAstroEvents(data) {
    const events = [];
    
    function extract(obj) {
        if (!obj || typeof obj !== 'object') return;
        
        if (Array.isArray(obj)) {
            obj.forEach(extract);
            return;
        }
        
        // Проверяем, похож ли объект на астрономическое событие
        if ((obj.type || obj.event_type || obj.category) && 
            (obj.name || obj.body || obj.object || obj.target)) {
            events.push({
                name: obj.name || obj.body || obj.object || obj.target || '',
                type: obj.type || obj.event_type || obj.category || '',
                when: obj.time || obj.date || obj.occursAt || obj.peak || obj.instant || ''
            });
        }
        
        // Рекурсивно ищем в дочерних объектах
        Object.values(obj).forEach(extract);
    }
    
    extract(data);
    return events;
}

// ====== ФУНКЦИИ УПРАВЛЕНИЯ ДАШБОРДОМ ======

// Обновление статистики дашборда
function updateDashboardStats() {
    // Эти значения будут обновляться при загрузке данных каждого виджета
    // Мы уже обновляем их в соответствующих функциях
}

// Обновление активных фильтров
function updateActiveFilters() {
    const container = document.getElementById('activeFilters');
    if (!container) return;
    
    let html = '';
    
    // Фильтр по времени
    if (currentFilters.timeRange !== '7d') {
        const label = {
            '24h': '24 часа',
            '30d': '30 дней',
            '90d': '90 дней'
        }[currentFilters.timeRange] || currentFilters.timeRange;
        
        html += `
            <span class="filter-tag">
                Период: ${label}
                <button type="button" class="btn-close btn-close-white ms-1" 
                        onclick="removeFilter('timeRange')"></button>
            </span>
        `;
    }
    
    // Фильтр по источникам (если не все выбраны)
    if (currentFilters.dataSources.length < 6) {
        html += `
            <span class="filter-tag">
                Источники: ${currentFilters.dataSources.length} из 6
                <button type="button" class="btn-close btn-close-white ms-1" 
                        onclick="removeFilter('dataSources')"></button>
            </span>
        `;
    }
    
    // Фильтр по типам виджетов
    if (currentFilters.widgetTypes !== 'all') {
        const label = {
            'charts': 'Графики',
            'tables': 'Таблицы',
            'maps': 'Карты',
            'stats': 'Статистика'
        }[currentFilters.widgetTypes] || currentFilters.widgetTypes;
        
        html += `
            <span class="filter-tag">
                Тип: ${label}
                <button type="button" class="btn-close btn-close-white ms-1" 
                        onclick="removeFilter('widgetTypes')"></button>
            </span>
        `;
    }
    
    container.innerHTML = html;
}

// Удаление фильтра
function removeFilter(filterName) {
    switch(filterName) {
        case 'timeRange':
            document.getElementById('timeRange').value = '7d';
            currentFilters.timeRange = '7d';
            break;
        case 'dataSources':
            const sourceSelect = document.getElementById('dataSources');
            Array.from(sourceSelect.options).forEach(option => {
                option.selected = true;
            });
            currentFilters.dataSources = ['iss', 'jwst', 'neo', 'apod', 'spacex', 'osdr'];
            break;
        case 'widgetTypes':
            document.getElementById('widgetTypes').value = 'all';
            currentFilters.widgetTypes = 'all';
            break;
    }
    
    updateActiveFilters();
    refreshDashboard();
}

// Сброс всех фильтров
function resetFilters() {
    document.getElementById('timeRange').value = '7d';
    document.getElementById('widgetTypes').value = 'all';
    document.getElementById('sortBy').value = 'custom';
    
    const sourceSelect = document.getElementById('dataSources');
    Array.from(sourceSelect.options).forEach(option => {
        option.selected = true;
    });
    
    currentFilters = {
        timeRange: '7d',
        dataSources: ['iss', 'jwst', 'neo', 'apod', 'spacex', 'osdr'],
        widgetTypes: 'all',
        sortBy: 'custom'
    };
    
    updateActiveFilters();
    showAllWidgets();
    restoreWidgetLayout();
}

// Фильтрация виджетов по источнику
function filterWidgetsBySource() {
    const widgets = document.querySelectorAll('.widget');
    
    widgets.forEach(widget => {
        const source = widget.getAttribute('data-source');
        const shouldShow = currentFilters.dataSources.includes(source);
        
        widget.style.display = shouldShow ? '' : 'none';
    });
}

// Фильтрация виджетов по типу
function filterWidgetsByType() {
    const widgets = document.querySelectorAll('.widget');
    const typeFilter = currentFilters.widgetTypes;
    
    if (typeFilter === 'all') {
        widgets.forEach(widget => {
            widget.style.display = '';
        });
        return;
    }
    
    widgets.forEach(widget => {
        const type = widget.getAttribute('data-type');
        const shouldShow = type === typeFilter;
        
        widget.style.display = shouldShow ? '' : 'none';
    });
}

// Показать все виджеты
function showAllWidgets() {
    const widgets = document.querySelectorAll('.widget');
    widgets.forEach(widget => {
        widget.style.display = '';
    });
}

// Сортировка виджетов
function sortWidgets() {
    const container = document.getElementById('dashboardWidgets');
    const widgets = Array.from(container.querySelectorAll('.widget'));
    
    switch(currentFilters.sortBy) {
        case 'name':
            widgets.sort((a, b) => {
                const nameA = a.querySelector('.widget-title')?.textContent || '';
                const nameB = b.querySelector('.widget-title')?.textContent || '';
                return nameA.localeCompare(nameB);
            });
            break;
            
        case 'importance':
            // Здесь можно добавить логику определения важности
            // Пока просто случайная сортировка для демо
            widgets.sort(() => Math.random() - 0.5);
            break;
            
        case 'update':
            // Сортировка по времени последнего обновления
            // Пока просто случайная сортировка для демо
            widgets.sort(() => Math.random() - 0.5);
            break;
            
        case 'custom':
            // Восстанавливаем сохраненный порядок
            restoreWidgetLayout();
            return;
    }
    
    // Переставляем виджеты
    widgets.forEach(widget => {
        container.appendChild(widget);
    });
    
    saveWidgetLayout();
}

// Сохранение расположения виджетов
function saveWidgetLayout() {
    const container = document.getElementById('dashboardWidgets');
    const widgetIds = Array.from(container.querySelectorAll('.widget'))
        .map(widget => widget.getAttribute('data-widget-id'));
    
    localStorage.setItem('dashboardLayout', JSON.stringify(widgetIds));
    
    // Показать уведомление
    showNotification('Расположение виджетов сохранено', 'success');
}

// Восстановление расположения виджетов
function restoreWidgetLayout() {
    const savedLayout = localStorage.getItem('dashboardLayout');
    if (!savedLayout) return;
    
    try {
        const widgetIds = JSON.parse(savedLayout);
        const container = document.getElementById('dashboardWidgets');
        const widgets = Array.from(container.querySelectorAll('.widget'));
        
        // Создаем мапу для быстрого доступа
        const widgetMap = {};
        widgets.forEach(widget => {
            const id = widget.getAttribute('data-widget-id');
            widgetMap[id] = widget;
        });
        
        // Переставляем виджеты в сохраненном порядке
        widgetIds.forEach(id => {
            const widget = widgetMap[id];
            if (widget) {
                container.appendChild(widget);
            }
        });
    } catch (error) {
        console.error('Ошибка восстановления layout:', error);
    }
}

// Обновление плотности виджетов
function updateWidgetDensity() {
    const density = document.getElementById('widgetDensity').value;
    const grid = document.getElementById('dashboardWidgets');
    
    switch(density) {
        case 'compact':
            grid.style.gap = '10px';
            break;
        case 'normal':
            grid.style.gap = '20px';
            break;
        case 'spacious':
            grid.style.gap = '30px';
            break;
    }
}

// ====== АВТООБНОВЛЕНИЕ ======

// Запуск автообновления
function startAutoRefresh() {
    const interval = parseInt(document.getElementById('refreshInterval').value) * 1000;
    
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(() => {
        if (document.getElementById('autoRefresh').checked) {
            refreshDashboard();
        }
    }, interval);
}

// Остановка автообновления
function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Переключение автообновления
function toggleAutoRefresh() {
    const enabled = document.getElementById('autoRefresh').checked;
    
    if (enabled) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}

// Обновление интервала автообновления
function updateRefreshInterval() {
    if (document.getElementById('autoRefresh').checked) {
        startAutoRefresh();
    }
}

// Обновление всего дашборда
function refreshDashboard() {
    loadWidgetData();
    showNotification('Данные обновлены', 'info');
}

// Обновление конкретного виджета
function refreshWidget(widgetId) {
    switch(widgetId) {
        case 'iss-map':
            loadIssData();
            break;
        case 'jwst-gallery':
            loadJwstGallery();
            break;
        case 'neo-table':
            loadNeoData();
            break;
        case 'apod-widget':
            loadApod();
            break;
        case 'spacex-next':
            loadSpaceXData();
            break;
        case 'osdr-stats':
            loadOsdrData();
            break;
        case 'astro-events':
            const days = document.getElementById('astroDays').value || 7;
            loadAstroEvents(days);
            break;
    }
    
    showNotification(`Виджет "${widgetId}" обновлен`, 'info');
}

// ====== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ======

// Показать уведомление
function showNotification(message, type = 'info') {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Добавляем в документ
    document.body.appendChild(notification);
    
    // Автоматически удаляем через 5 секунд
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Переключение полноэкранного режима
function toggleFullscreen(widgetId) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (!widget) return;
    
    if (!document.fullscreenElement) {
        if (widget.requestFullscreen) {
            widget.requestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Экспорт данных
function exportChartData(chartType) {
    // Здесь будет логика экспорта данных
    showNotification('Экспорт данных (функция в разработке)', 'warning');
}

// Обновление статистики JWST
function updateJwstStats(count) {
    document.getElementById('statJwstCount').textContent = count;
}

// Обновление статистики NEO
function updateNeoStats(data) {
    const items = data.items || data.asteroids || [];
    document.getElementById('statNeoCount').textContent = items.length;
}

// Обновление статистики ISS
function updateIssStats(data) {
    const points = data.points || [];
    if (points.length > 0) {
        const last = points[points.length - 1];
        document.getElementById('statIssSpeed').textContent = 
            Math.round(last.velocity || 0).toLocaleString();
        document.getElementById('statIssAltitude').textContent = 
            Math.round(last.altitude || 0).toLocaleString();
    }
}

// ====== ОБРАБОТЧИКИ ДЛЯ КНОПОК ВИДЖЕТОВ ======

// JWST: Фильтрация галереи
function filterJwstGallery(filter) {
    loadJwstGallery(filter);
}

// JWST: Загрузить больше
function loadMoreJwst() {
    showNotification('Загрузка дополнительных изображений...', 'info');
    // Здесь будет логика пагинации
}

// NEO: Показать только опасные
function showOnlyHazardous() {
    // Здесь будет логика фильтрации
    showNotification('Показаны только опасные астероиды', 'info');
}

// APOD: Показать случайную
function showRandomApod() {
    fetch('/api/apod/random')
        .then(response => response.json())
        .then(data => {
            updateApodWidget(data);
            showNotification('Загружена случайная картина дня', 'success');
        })
        .catch(error => {
            console.error('Ошибка загрузки случайной APOD:', error);
            showNotification('Ошибка загрузки случайной APOD', 'danger');
        });
}

// SpaceX: Обновить данные
function refreshSpaceX() {
    loadSpaceXData();
}

// OSDR: Экспорт статистики
function exportOsdrStats() {
    showNotification('Экспорт статистики OSDR (функция в разработке)', 'warning');
}

// Астрономические события: Обновить с новым периодом
function updateAstroEvents() {
    const days = document.getElementById('astroDays').value || 7;
    loadAstroEvents(days);
}

// ISS: Изменение типа графика
function changeChartType(chartName, chartType) {
    // Здесь будет логика изменения типа графика
    showNotification(`Тип графика изменен на ${chartType}`, 'info');
}

// Инициализация при загрузке страницы
window.onload = function() {
    // Добавляем обработчик изменения полноэкранного режима
    document.addEventListener('fullscreenchange', function() {
        const fullscreenBtn = document.querySelector('[onclick*="toggleFullscreen"]');
        if (fullscreenBtn) {
            const icon = fullscreenBtn.querySelector('i');
            if (icon) {
                icon.className = document.fullscreenElement ? 
                    'fas fa-compress' : 'fas fa-expand';
            }
        }
    });
};
</script>
@endpush