@extends('layouts.app')

@section('title', 'SpaceX - Запуски и ракеты')

@push('styles')
<style>
    .spacex-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 45, 85, 0.3);
        border-radius: 15px;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .spacex-card:hover {
        transform: translateY(-5px);
        border-color: #ff2d55;
        box-shadow: 0 15px 30px rgba(255, 45, 85, 0.2);
    }
    
    .countdown {
        font-family: 'Courier New', monospace;
        font-size: 2rem;
        text-align: center;
        margin: 20px 0;
    }
    
    .countdown-unit {
        display: inline-block;
        margin: 0 10px;
    }
    
    .countdown-value {
        background: rgba(255, 45, 85, 0.2);
        padding: 10px 15px;
        border-radius: 10px;
        min-width: 80px;
    }
    
    .countdown-label {
        font-size: 0.8rem;
        color: #a0a0c0;
        margin-top: 5px;
    }
    
    .launch-badge {
        font-size: 0.8rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
    
    .success-badge {
        background: rgba(76, 217, 100, 0.2);
        color: #4cd964;
        border: 1px solid #4cd964;
    }
    
    .failed-badge {
        background: rgba(255, 59, 48, 0.2);
        color: #ff3b30;
        border: 1px solid #ff3b30;
    }
    
    .upcoming-badge {
        background: rgba(255, 149, 0, 0.2);
        color: #ff9500;
        border: 1px solid #ff9500;
        animation: pulse-warning 2s infinite;
    }
    
    @keyframes pulse-warning {
        0% { box-shadow: 0 0 0 0 rgba(255, 149, 0, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 149, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 149, 0, 0); }
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
        margin: 20px 0;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: rgba(255, 45, 85, 0.3);
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
        padding-left: 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #ff2d55;
        border: 2px solid rgba(255, 45, 85, 0.5);
    }
    
    .timeline-date {
        color: #ff2d55;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .rocket-card {
        background: linear-gradient(135deg, rgba(255, 45, 85, 0.1) 0%, rgba(0, 180, 216, 0.1) 100%);
        border: 1px solid rgba(255, 45, 85, 0.3);
        border-radius: 10px;
        padding: 15px;
        height: 100%;
        transition: all 0.3s ease;
    }
    
    .rocket-card:hover {
        transform: scale(1.05);
        border-color: #ff2d55;
    }
    
    .stat-card {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.05);
        margin-bottom: 15px;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .stat-label {
        color: #a0a0c0;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .crew-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 45, 85, 0.5);
        margin-bottom: 10px;
    }
    
    .patch-image {
        width: 100px;
        height: 100px;
        object-fit: contain;
    }
    
    .map-container {
        height: 300px;
        border-radius: 10px;
        overflow: hidden;
        margin: 20px 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <!-- Заголовок и следующий запуск -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card bg-dark border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mb-0">
                            <i class="fas fa-rocket text-danger me-2"></i>SpaceX Missions
                        </h1>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-danger" onclick="refreshData()">
                                <i class="fas fa-sync-alt me-1"></i> Обновить
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="scrollToNextLaunch()">
                                <i class="fas fa-forward me-1"></i> Следующий запуск
                            </button>
                        </div>
                    </div>
                    <p class="text-muted mb-0 mt-2">
                        Данные о запусках, ракетах и миссиях SpaceX. Обновлено: 
                        <span id="lastUpdated">{{ now()->format('d.m.Y H:i') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Следующий запуск -->
    <div class="row mb-4 fade-in" id="nextLaunchSection">
        <div class="col-12">
            <div class="card spacex-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="text-danger mb-3">
                                <i class="fas fa-forward me-2"></i>Следующий запуск
                            </h3>
                            
                            <div class="mb-4" id="nextLaunchInfo">
                                <div class="text-center py-5">
                                    <div class="loading-spinner"></div>
                                    <p class="text-muted mt-3">Загрузка данных о следующем запуске...</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="mb-3">Обратный отсчет</h4>
                                <div class="countdown" id="countdown">
                                    <div class="countdown-unit">
                                        <div class="countdown-value" id="days">00</div>
                                        <div class="countdown-label">Дней</div>
                                    </div>
                                    <div class="countdown-unit">
                                        <div class="countdown-value" id="hours">00</div>
                                        <div class="countdown-label">Часов</div>
                                    </div>
                                    <div class="countdown-unit">
                                        <div class="countdown-value" id="minutes">00</div>
                                        <div class="countdown-label">Минут</div>
                                    </div>
                                    <div class="countdown-unit">
                                        <div class="countdown-value" id="seconds">00</div>
                                        <div class="countdown-label">Секунд</div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="btn btn-danger w-100" onclick="watchLivestream()" id="watchBtn" disabled>
                                        <i class="fab fa-youtube me-2"></i> Смотреть трансляцию
                                    </button>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Время указано по UTC
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Фильтры запусков -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-filter me-2"></i>Фильтры запусков
                    </h5>
                    
                    <form id="launchFilters" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Статус</label>
                            <select class="form-select" id="statusFilter">
                                <option value="all">Все</option>
                                <option value="upcoming">Предстоящие</option>
                                <option value="success">Успешные</option>
                                <option value="failed">Неудачные</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Год</label>
                            <select class="form-select" id="yearFilter">
                                <option value="all">Все годы</option>
                                <!-- Годы будут добавлены через JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Ракета</label>
                            <select class="form-select" id="rocketFilter">
                                <option value="all">Все ракеты</option>
                                <!-- Ракеты будут добавлены через JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Сортировка</label>
                            <select class="form-select" id="sortFilter">
                                <option value="date_desc">По дате (новые)</option>
                                <option value="date_asc">По дате (старые)</option>
                                <option value="name">По названию</option>
                            </select>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-danger" onclick="applyFilters()">
                            <i class="fas fa-check me-1"></i> Применить фильтры
                        </button>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="resetFilters()">
                            <i class="fas fa-times me-1"></i> Сбросить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Статистика -->
    <div class="row mb-4 fade-in">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-danger" id="totalLaunches">0</div>
                <div class="stat-label">Всего запусков</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-success" id="successLaunches">0</div>
                <div class="stat-label">Успешных</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-warning" id="upcomingLaunches">0</div>
                <div class="stat-label">Предстоящих</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-info" id="successRate">0%</div>
                <div class="stat-label">Успешность</div>
            </div>
        </div>
    </div>
    
    <!-- Список запусков -->
    <div class="row fade-in">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>
                    <i class="fas fa-list me-2"></i>Запуски SpaceX
                    <span class="badge bg-danger" id="launchesCount">0</span>
                </h3>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm w-auto" id="perPage">
                        <option value="10">10 на странице</option>
                        <option value="25" selected>25 на странице</option>
                        <option value="50">50 на странице</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" onclick="exportLaunches()">
                        <i class="fas fa-download me-1"></i> Экспорт
                    </button>
                </div>
            </div>
            
            <div class="row" id="launchesGrid">
                <!-- Запуски будут загружены через JavaScript -->
                <div class="col-12 text-center py-5">
                    <div class="loading-spinner"></div>
                    <p class="text-muted mt-3">Загрузка данных о запусках...</p>
                </div>
            </div>
            
            <!-- Пагинация -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    Показано <span id="showingFrom">1</span>-<span id="showingTo">25</span> из 
                    <span id="totalLaunchesCount">0</span>
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Ракеты -->
    <div class="row fade-in">
        <div class="col-12 mb-4">
            <h3 class="mb-3">
                <i class="fas fa-space-shuttle me-2"></i>Ракеты SpaceX
            </h3>
            
            <div class="row" id="rocketsGrid">
                <!-- Ракеты будут загружены через JavaScript -->
                <div class="col-12 text-center py-5">
                    <div class="loading-spinner"></div>
                    <p class="text-muted mt-3">Загрузка данных о ракетах...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Таймлайн запусков -->
    <div class="row fade-in">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-timeline me-2"></i>Таймлайн запусков (последние 12 месяцев)
                    </h5>
                    
                    <div class="timeline" id="timeline">
                        <!-- Таймлайн будет создан через JavaScript -->
                        <div class="text-center py-5">
                            <div class="loading-spinner"></div>
                            <p class="text-muted mt-3">Загрузка таймлайна...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно деталей запуска -->
<div class="modal fade" id="launchModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark">
            <div class="modal-header border-danger">
                <h5 class="modal-title">
                    <i class="fas fa-rocket me-2"></i>Детали запуска
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="launchModalBody">
                <!-- Контент будет загружен через JavaScript -->
                <div class="text-center py-5">
                    <div class="loading-spinner"></div>
                    <p class="text-muted mt-3">Загрузка данных...</p>
                </div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-danger" onclick="openLaunchDetails()">
                    <i class="fas fa-external-link-alt me-1"></i> Полная информация
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно деталей ракеты -->
<div class="modal fade" id="rocketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-info">
                <h5 class="modal-title">
                    <i class="fas fa-space-shuttle me-2"></i>Детали ракеты
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="rocketModalBody">
                <!-- Контент будет загружен через JavaScript -->
                <div class="text-center py-5">
                    <div class="loading-spinner"></div>
                    <p class="text-muted mt-3">Загрузка данных...</p>
                </div>
            </div>
            <div class="modal-footer border-info">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-info" onclick="openRocketDetails()">
                    <i class="fas fa-external-link-alt me-1"></i> Больше информации
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Глобальные переменные
let launches = [];
let rockets = [];
let nextLaunch = null;
let filteredLaunches = [];
let currentPage = 1;
let perPage = 25;
let countdownInterval = null;

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    loadNextLaunch();
    loadLaunches();
    loadRockets();
    setupEventListeners();
});

// Загрузка следующего запуска
function loadNextLaunch() {
    fetch('/api/spacex/next')
        .then(response => response.json())
        .then(data => {
            displayNextLaunch(data);
            nextLaunch = data;
            startCountdown();
        })
        .catch(error => {
            console.error('Ошибка загрузки следующего запуска:', error);
            document.getElementById('nextLaunchInfo').innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h4>Ошибка загрузки</h4>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Отображение следующего запуска
function displayNextLaunch(launch) {
    const container = document.getElementById('nextLaunchInfo');
    
    if (!launch || !launch.name) {
        container.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-rocket fa-3x mb-3"></i>
                <h4>Нет данных о предстоящих запусках</h4>
                <p>Информация о следующих запусках временно недоступна</p>
            </div>
        `;
        return;
    }
    
    const launchDate = new Date(launch.date_utc);
    const now = new Date();
    const timeDiff = launchDate.getTime() - now.getTime();
    const daysUntil = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    // Проверяем наличие трансляции
    const hasWebcast = launch.links && launch.links.webcast;
    const watchBtn = document.getElementById('watchBtn');
    if (hasWebcast && daysUntil <= 7) { // Трансляция доступна за неделю до запуска
        watchBtn.disabled = false;
        watchBtn.onclick = () => window.open(launch.links.webcast, '_blank');
    }
    
    container.innerHTML = `
        <h4 class="mb-3">${launch.name}</h4>
        
        ${launch.details ? `
            <p class="text-muted mb-4">${launch.details.substring(0, 200)}...</p>
        ` : ''}
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <small class="text-muted d-block">Дата запуска</small>
                    <strong>${launchDate.toLocaleDateString()} ${launchDate.toLocaleTimeString()}</strong>
                </div>
                ${launch.launchpad ? `
                    <div class="mb-3">
                        <small class="text-muted d-block">Площадка запуска</small>
                        <strong>${launch.launchpad}</strong>
                    </div>
                ` : ''}
            </div>
            <div class="col-md-6">
                ${launch.rocket ? `
                    <div class="mb-3">
                        <small class="text-muted d-block">Ракета</small>
                        <strong>${launch.rocket}</strong>
                    </div>
                ` : ''}
                ${daysUntil > 0 ? `
                    <div class="mb-3">
                        <small class="text-muted d-block">До запуска</small>
                        <strong class="text-warning">${daysUntil} дней</strong>
                    </div>
                ` : `
                    <div class="mb-3">
                        <small class="text-muted d-block">Статус</small>
                        <span class="badge upcoming-badge">Запуск скоро</span>
                    </div>
                `}
            </div>
        </div>
        
        ${launch.crew && launch.crew.length > 0 ? `
            <div class="mt-4">
                <h6>Экипаж</h6>
                <div class="d-flex flex-wrap gap-3">
                    ${launch.crew.map(member => `
                        <div class="text-center">
                            <div class="crew-avatar bg-secondary d-flex align-items-center justify-content-center">
                                <i class="fas fa-user-astronaut fa-2x"></i>
                            </div>
                            <small>${member.name || 'Астронавт'}</small>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : ''}
        
        ${launch.links && launch.links.patch && launch.links.patch.large ? `
            <div class="text-center mt-4">
                <img src="${launch.links.patch.large}" 
                     alt="Mission Patch" 
                     class="patch-image">
                <p class="small text-muted mt-2">Эмблема миссии</p>
            </div>
        ` : ''}
    `;
}

// Запуск обратного отсчета
function startCountdown() {
    if (!nextLaunch || !nextLaunch.date_utc) return;
    
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    const launchDate = new Date(nextLaunch.date_utc);
    
    function updateCountdown() {
        const now = new Date();
        const diff = launchDate.getTime() - now.getTime();
        
        if (diff <= 0) {
            // Запуск произошел
            document.getElementById('days').textContent = '00';
            document.getElementById('hours').textContent = '00';
            document.getElementById('minutes').textContent = '00';
            document.getElementById('seconds').textContent = '00';
            clearInterval(countdownInterval);
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        document.getElementById('days').textContent = days.toString().padStart(2, '0');
        document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
}

// Загрузка запусков
function loadLaunches() {
    fetch('/api/spacex/launches')
        .then(response => response.json())
        .then(data => {
            launches = data.launches || data;
            filteredLaunches = [...launches];
            
            updateLaunchesGrid();
            updateStats();
            populateFilters();
            createTimeline();
        })
        .catch(error => {
            console.error('Ошибка загрузки запусков:', error);
            document.getElementById('launchesGrid').innerHTML = `
                <div class="col-12 text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Ошибка загрузки</h4>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Загрузка ракет
function loadRockets() {
    fetch('/api/spacex/rockets')
        .then(response => response.json())
        .then(data => {
            rockets = data.rockets || data;
            displayRockets();
        })
        .catch(error => {
            console.error('Ошибка загрузки ракет:', error);
            document.getElementById('rocketsGrid').innerHTML = `
                <div class="col-12 text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Ошибка загрузки</h4>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Обновление сетки запусков
function updateLaunchesGrid() {
    const grid = document.getElementById('launchesGrid');
    const totalItems = filteredLaunches.length;
    
    // Пагинация
    const startIndex = (currentPage - 1) * perPage;
    const endIndex = Math.min(startIndex + perPage, totalItems);
    const pageItems = filteredLaunches.slice(startIndex, endIndex);
    
    // Обновление счетчиков
    document.getElementById('showingFrom').textContent = startIndex + 1;
    document.getElementById('showingTo').textContent = endIndex;
    document.getElementById('totalLaunchesCount').textContent = totalItems;
    document.getElementById('launchesCount').textContent = totalItems;
    
    // Очистка сетки
    grid.innerHTML = '';
    
    if (pageItems.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-rocket fa-3x mb-3"></i>
                    <h4>Запуски не найдены</h4>
                    <p>Попробуйте изменить параметры фильтрации</p>
                </div>
            </div>
        `;
        updatePagination(totalItems);
        return;
    }
    
    // Заполнение сетки
    pageItems.forEach(launch => {
        const launchDate = new Date(launch.date_utc);
        const isUpcoming = launch.upcoming;
        const isSuccess = launch.success;
        
        let statusBadge = '';
        if (isUpcoming) {
            statusBadge = '<span class="badge upcoming-badge">Предстоящий</span>';
        } else if (isSuccess) {
            statusBadge = '<span class="badge success-badge">Успешный</span>';
        } else {
            statusBadge = '<span class="badge failed-badge">Неудачный</span>';
        }
        
        const launchCard = document.createElement('div');
        launchCard.className = 'col-md-6 col-lg-4 mb-4';
        launchCard.innerHTML = `
            <div class="card spacex-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0" style="font-size: 1.1rem;">
                            ${launch.name}
                        </h5>
                        ${statusBadge}
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Дата запуска</small>
                        <strong>${launchDate.toLocaleDateString()}</strong>
                        <br>
                        <small class="text-muted">${launchDate.toLocaleTimeString()}</small>
                    </div>
                    
                    ${launch.details ? `
                        <p class="small text-muted" style="height: 60px; overflow: hidden;">
                            ${launch.details.substring(0, 100)}...
                        </p>
                    ` : ''}
                    
                    ${launch.links && launch.links.patch && launch.links.patch.small ? `
                        <div class="text-center mb-3">
                            <img src="${launch.links.patch.small}" 
                                 alt="Mission Patch" 
                                 style="width: 80px; height: 80px; object-fit: contain;">
                        </div>
                    ` : ''}
                    
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-danger w-100" 
                                onclick="showLaunchDetails('${launch.id}')">
                            <i class="fas fa-info-circle me-1"></i> Подробнее
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        grid.appendChild(launchCard);
    });
    
    updatePagination(totalItems);
}

// Обновление пагинации
function updatePagination(totalItems) {
    const pagination = document.getElementById('pagination');
    const totalPages = Math.ceil(totalItems / perPage);
    
    // Очистка пагинации
    pagination.innerHTML = '';
    
    // Кнопка "Назад"
    const prevButton = document.createElement('li');
    prevButton.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevButton.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1})" tabindex="-1">
            <i class="fas fa-chevron-left"></i>
        </a>
    `;
    pagination.appendChild(prevButton);
    
    // Номера страниц
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageItem.innerHTML = `
            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
        `;
        pagination.appendChild(pageItem);
    }
    
    // Кнопка "Вперед"
    const nextButton = document.createElement('li');
    nextButton.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextButton.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </a>
    `;
    pagination.appendChild(nextButton);
}

// Смена страницы
function changePage(page) {
    const totalPages = Math.ceil(filteredLaunches.length / perPage);
    
    if (page < 1 || page > totalPages) {
        return;
    }
    
    currentPage = page;
    updateLaunchesGrid();
}

// Отображение ракет
function displayRockets() {
    const grid = document.getElementById('rocketsGrid');
    
    if (!rockets || rockets.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center text-muted py-5">
                <i class="fas fa-space-shuttle fa-3x mb-3"></i>
                <h4>Нет данных о ракетах</h4>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = '';
    
    rockets.forEach(rocket => {
        const rocketCard = document.createElement('div');
        rocketCard.className = 'col-md-6 col-lg-3 mb-4';
        rocketCard.innerHTML = `
            <div class="rocket-card">
                <h5 class="mb-3">${rocket.name}</h5>
                
                <div class="mb-2">
                    <small class="text-muted d-block">Статус</small>
                    <span class="badge ${rocket.active ? 'bg-success' : 'bg-secondary'}">
                        ${rocket.active ? 'Активная' : 'Снята с производства'}
                    </span>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted d-block">Запусков</small>
                    <strong>${rocket.launches || 0}</strong>
                </div>
                
                ${rocket.success_rate_pct ? `
                    <div class="mb-2">
                        <small class="text-muted d-block">Успешность</small>
                        <strong>${rocket.success_rate_pct}%</strong>
                    </div>
                ` : ''}
                
                ${rocket.cost_per_launch ? `
                    <div class="mb-2">
                        <small class="text-muted d-block">Стоимость запуска</small>
                        <strong>$${rocket.cost_per_launch.toLocaleString()}</strong>
                    </div>
                ` : ''}
                
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-info w-100" 
                            onclick="showRocketDetails('${rocket.id}')">
                        <i class="fas fa-info-circle me-1"></i> Подробнее
                    </button>
                </div>
            </div>
        `;
        
        grid.appendChild(rocketCard);
    });
}

// Создание таймлайна
function createTimeline() {
    const timeline = document.getElementById('timeline');
    
    if (!launches || launches.length === 0) {
        timeline.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-timeline"></i> Нет данных для таймлайна
            </div>
        `;
        return;
    }
    
    // Берем последние 20 запусков
    const recentLaunches = launches
        .filter(l => !l.upcoming)
        .sort((a, b) => new Date(b.date_utc) - new Date(a.date_utc))
        .slice(0, 20)
        .reverse();
    
    let timelineHTML = '';
    
    recentLaunches.forEach(launch => {
        const launchDate = new Date(launch.date_utc);
        const isSuccess = launch.success;
        
        timelineHTML += `
            <div class="timeline-item">
                <div class="timeline-date">
                    ${launchDate.toLocaleDateString()}
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${launch.name}</strong>
                        <br>
                        <small class="text-muted">${launch.rocket || ''}</small>
                    </div>
                    <span class="badge ${isSuccess ? 'success-badge' : 'failed-badge'}">
                        ${isSuccess ? 'Успех' : 'Неудача'}
                    </span>
                </div>
            </div>
        `;
    });
    
    timeline.innerHTML = timelineHTML;
}

// Заполнение фильтров
function populateFilters() {
    const yearSelect = document.getElementById('yearFilter');
    const rocketSelect = document.getElementById('rocketFilter');
    
    // Извлекаем уникальные годы
    const years = new Set();
    launches.forEach(launch => {
        if (launch.date_utc) {
            const year = new Date(launch.date_utc).getFullYear();
            years.add(year);
        }
    });
    
    // Сортируем годы по убыванию и добавляем в селект
    const sortedYears = Array.from(years).sort((a, b) => b - a);
    sortedYears.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    });
    
    // Извлекаем уникальные ракеты
    const rockets = new Set();
    launches.forEach(launch => {
        if (launch.rocket) {
            rockets.add(launch.rocket);
        }
    });
    
    // Добавляем ракеты в селект
    rockets.forEach(rocket => {
        const option = document.createElement('option');
        option.value = rocket;
        option.textContent = rocket;
        rocketSelect.appendChild(option);
    });
}

// Применение фильтров
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const year = document.getElementById('yearFilter').value;
    const rocket = document.getElementById('rocketFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    filteredLaunches = launches.filter(launch => {
        // Фильтр по статусу
        let statusMatch = true;
        if (status === 'upcoming') {
            statusMatch = launch.upcoming === true;
        } else if (status === 'success') {
            statusMatch = launch.success === true && launch.upcoming === false;
        } else if (status === 'failed') {
            statusMatch = launch.success === false && launch.upcoming === false;
        }
        
        // Фильтр по году
        let yearMatch = true;
        if (year !== 'all' && launch.date_utc) {
            const launchYear = new Date(launch.date_utc).getFullYear();
            yearMatch = launchYear.toString() === year;
        }
        
        // Фильтр по ракете
        let rocketMatch = true;
        if (rocket !== 'all') {
            rocketMatch = launch.rocket === rocket;
        }
        
        return statusMatch && yearMatch && rocketMatch;
    });
    
    // Сортировка
    filteredLaunches.sort((a, b) => {
        switch(sort) {
            case 'date_asc':
                return new Date(a.date_utc) - new Date(b.date_utc);
            case 'name':
                return (a.name || '').localeCompare(b.name || '');
            case 'date_desc':
            default:
                return new Date(b.date_utc) - new Date(a.date_utc);
        }
    });
    
    currentPage = 1;
    updateLaunchesGrid();
    updateStats();
}

// Сброс фильтров
function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('yearFilter').value = 'all';
    document.getElementById('rocketFilter').value = 'all';
    document.getElementById('sortFilter').value = 'date_desc';
    
    filteredLaunches = [...launches];
    currentPage = 1;
    updateLaunchesGrid();
    updateStats();
}

// Обновление статистики
function updateStats() {
    const total = filteredLaunches.length;
    const successful = filteredLaunches.filter(l => l.success && !l.upcoming).length;
    const upcoming = filteredLaunches.filter(l => l.upcoming).length;
    const completed = filteredLaunches.filter(l => !l.upcoming).length;
    const successRate = completed > 0 ? Math.round((successful / completed) * 100) : 0;
    
    document.getElementById('totalLaunches').textContent = total;
    document.getElementById('successLaunches').textContent = successful;
    document.getElementById('upcomingLaunches').textContent = upcoming;
    document.getElementById('successRate').textContent = `${successRate}%`;
}

// Показать детали запуска
function showLaunchDetails(launchId) {
    const modal = new bootstrap.Modal(document.getElementById('launchModal'));
    const modalBody = document.getElementById('launchModalBody');
    
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-muted mt-3">Загрузка данных...</p>
        </div>
    `;
    
    modal.show();
    
    // Ищем запуск в данных
    const launch = launches.find(l => l.id === launchId);
    
    if (launch) {
        displayLaunchDetails(launch);
    } else {
        // Загружаем с сервера
        fetch(`/api/spacex/launch/${launchId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayLaunchDetails(data.launch);
                } else {
                    modalBody.innerHTML = `
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h4>Ошибка загрузки</h4>
                            <p>${data.message || 'Не удалось загрузить данные'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                modalBody.innerHTML = `
                    <div class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>Ошибка</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    }
}

// Отображение деталей запуска
function displayLaunchDetails(launch) {
    const modalBody = document.getElementById('launchModalBody');
    const launchDate = new Date(launch.date_utc);
    const isUpcoming = launch.upcoming;
    const isSuccess = launch.success;
    
    let statusBadge = '';
    if (isUpcoming) {
        statusBadge = '<span class="badge upcoming-badge">Предстоящий</span>';
    } else if (isSuccess) {
        statusBadge = '<span class="badge success-badge">Успешный</span>';
    } else {
        statusBadge = '<span class="badge failed-badge">Неудачный</span>';
    }
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h4 class="mb-3">${launch.name}</h4>
                <div class="mb-3">
                    ${statusBadge}
                </div>
                
                ${launch.details ? `
                    <div class="mb-4">
                        <h6>Описание миссии</h6>
                        <p class="text-muted">${launch.details}</p>
                    </div>
                ` : ''}
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted d-block">Дата запуска</small>
                            <strong>${launchDate.toLocaleDateString()} ${launchDate.toLocaleTimeString()}</strong>
                        </div>
                        ${launch.launchpad ? `
                            <div class="mb-3">
                                <small class="text-muted d-block">Площадка запуска</small>
                                <strong>${launch.launchpad}</strong>
                            </div>
                        ` : ''}
                    </div>
                    <div class="col-md-6">
                        ${launch.rocket ? `
                            <div class="mb-3">
                                <small class="text-muted d-block">Ракета</small>
                                <strong>${launch.rocket}</strong>
                            </div>
                        ` : ''}
                        ${launch.flight_number ? `
                            <div class="mb-3">
                                <small class="text-muted d-block">Номер полета</small>
                                <strong>${launch.flight_number}</strong>
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                ${launch.crew && launch.crew.length > 0 ? `
                    <div class="mb-4">
                        <h6>Экипаж</h6>
                        <div class="row">
                            ${launch.crew.map(member => `
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-darker">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="crew-avatar bg-secondary me-3 d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user-astronaut"></i>
                                                </div>
                                                <div>
                                                    <strong>${member.name || 'Астронавт'}</strong>
                                                    <br>
                                                    <small class="text-muted">${member.role || 'Член экипажа'}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${launch.payloads && launch.payloads.length > 0 ? `
                    <div class="mb-4">
                        <h6>Полезная нагрузка</h6>
                        <div class="row">
                            ${launch.payloads.map(payload => `
                                <div class="col-md-6 mb-2">
                                    <div class="card bg-darker">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">${payload.type || 'Полезная нагрузка'}</small>
                                            <strong>${payload.name || 'Неизвестно'}</strong>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="col-md-4">
                ${launch.links && launch.links.patch && launch.links.patch.large ? `
                    <div class="text-center mb-4">
                        <img src="${launch.links.patch.large}" 
                             alt="Mission Patch" 
                             class="img-fluid rounded">
                        <p class="small text-muted mt-2">Эмблема миссии</p>
                    </div>
                ` : ''}
                
                ${launch.links ? `
                    <div class="card bg-darker">
                        <div class="card-body">
                            <h6>Ссылки</h6>
                            <div class="d-flex flex-column gap-2">
                                ${launch.links.webcast ? `
                                    <a href="${launch.links.webcast}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-danger">
                                        <i class="fab fa-youtube me-1"></i> Трансляция
                                    </a>
                                ` : ''}
                                ${launch.links.article ? `
                                    <a href="${launch.links.article}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-newspaper me-1"></i> Статья
                                    </a>
                                ` : ''}
                                ${launch.links.wikipedia ? `
                                    <a href="${launch.links.wikipedia}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fab fa-wikipedia-w me-1"></i> Wikipedia
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                ` : ''}
                
                ${launch.launchpad_details ? `
                    <div class="card bg-darker mt-3">
                        <div class="card-body">
                            <h6>Информация о площадке</h6>
                            <p class="small text-muted mb-0">${launch.launchpad_details}</p>
                        </div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

// Показать детали ракеты
function showRocketDetails(rocketId) {
    const modal = new bootstrap.Modal(document.getElementById('rocketModal'));
    const modalBody = document.getElementById('rocketModalBody');
    
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-muted mt-3">Загрузка данных...</p>
        </div>
    `;
    
    modal.show();
    
    // Ищем ракету в данных
    const rocket = rockets.find(r => r.id === rocketId);
    
    if (rocket) {
        displayRocketDetails(rocket);
    } else {
        // Загружаем с сервера
        fetch(`/api/spacex/rocket/${rocketId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRocketDetails(data.rocket);
                } else {
                    modalBody.innerHTML = `
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h4>Ошибка загрузки</h4>
                            <p>${data.message || 'Не удалось загрузить данные'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                modalBody.innerHTML = `
                    <div class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>Ошибка</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    }
}

// Отображение деталей ракеты
function displayRocketDetails(rocket) {
    const modalBody = document.getElementById('rocketModalBody');
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h4 class="mb-3">${rocket.name}</h4>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Статус</small>
                    <span class="badge ${rocket.active ? 'bg-success' : 'bg-secondary'}">
                        ${rocket.active ? 'Активная' : 'Снята с производства'}
                    </span>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Первый полет</small>
                    <strong>${rocket.first_flight || 'Неизвестно'}</strong>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Страна</small>
                    <strong>${rocket.country || 'США'}</strong>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Компания</small>
                    <strong>${rocket.company || 'SpaceX'}</strong>
                </div>
                
                ${rocket.description ? `
                    <div class="mb-4">
                        <h6>Описание</h6>
                        <p class="small text-muted">${rocket.description}</p>
                    </div>
                ` : ''}
            </div>
            <div class="col-md-6">
                <div class="card bg-darker">
                    <div class="card-body">
                        <h6>Технические характеристики</h6>
                        
                        <div class="row">
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Высота</small>
                                <strong>${rocket.height?.meters || '—'} м</strong>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Диаметр</small>
                                <strong>${rocket.diameter?.meters || '—'} м</strong>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Масса</small>
                                <strong>${rocket.mass?.kg ? (rocket.mass.kg / 1000).toFixed(0) + ' т' : '—'}</strong>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Ступеней</small>
                                <strong>${rocket.stages || '—'}</strong>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Запусков</small>
                                <strong>${rocket.launches || 0}</strong>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Успешность</small>
                                <strong>${rocket.success_rate_pct || 0}%</strong>
                            </div>
                            ${rocket.cost_per_launch ? `
                                <div class="col-12 mb-2">
                                    <small class="text-muted d-block">Стоимость запуска</small>
                                    <strong>$${rocket.cost_per_launch.toLocaleString()}</strong>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                ${rocket.flickr_images && rocket.flickr_images.length > 0 ? `
                    <div class="mt-3">
                        <h6>Изображения</h6>
                        <div class="row">
                            ${rocket.flickr_images.slice(0, 2).map(img => `
                                <div class="col-6 mb-2">
                                    <img src="${img}" 
                                         class="img-fluid rounded" 
                                         alt="${rocket.name}"
                                         style="height: 100px; object-fit: cover;">
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

// Экспорт запусков
function exportLaunches() {
    if (filteredLaunches.length === 0) {
        showNotification('Нет данных для экспорта', 'warning');
        return;
    }
    
    // Создаем CSV
    let csv = 'Название,Дата,Ракета,Статус,Успешность,Площадка\n';
    
    filteredLaunches.forEach(launch => {
        const launchDate = new Date(launch.date_utc);
        let status = '';
        
        if (launch.upcoming) {
            status = 'Предстоящий';
        } else if (launch.success) {
            status = 'Успешный';
        } else {
            status = 'Неудачный';
        }
        
        csv += `"${launch.name || ''}","${launchDate.toLocaleDateString()}","${launch.rocket || ''}",`;
        csv += `"${status}","${launch.success ? 'Да' : 'Нет'}","${launch.launchpad || ''}"\n`;
    });
    
    // Создаем Blob и скачиваем
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `spacex_launches_${new Date().toISOString().slice(0, 10)}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Запуски экспортированы в CSV', 'success');
}

// Прокрутка к следующему запуску
function scrollToNextLaunch() {
    document.getElementById('nextLaunchSection').scrollIntoView({ 
        behavior: 'smooth' 
    });
}

// Смотреть трансляцию
function watchLivestream() {
    if (nextLaunch && nextLaunch.links && nextLaunch.links.webcast) {
        window.open(nextLaunch.links.webcast, '_blank');
    } else {
        showNotification('Трансляция пока недоступна', 'warning');
    }
}

// Обновить данные
function refreshData() {
    loadNextLaunch();
    loadLaunches();
    loadRockets();
    
    document.getElementById('lastUpdated').textContent = new Date().toLocaleString();
    showNotification('Данные обновлены', 'success');
}

// Открыть детали запуска (полная информация)
function openLaunchDetails() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('launchModal'));
    if (modal) modal.hide();
    
    // Здесь можно добавить перенаправление на страницу деталей запуска
    showNotification('Полная информация о запуске', 'info');
}

// Открыть детали ракеты (больше информации)
function openRocketDetails() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('rocketModal'));
    if (modal) modal.hide();
    
    // Здесь можно добавить перенаправление на страницу деталей ракеты
    showNotification('Больше информации о ракете', 'info');
}

// Настройка обработчиков событий
function setupEventListeners() {
    // Применение фильтров при изменении значений
    ['statusFilter', 'yearFilter', 'rocketFilter', 'sortFilter'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                // Автоматическое применение фильтров с задержкой
                clearTimeout(window.filterTimeout);
                window.filterTimeout = setTimeout(() => {
                    applyFilters();
                }, 500);
            });
        }
    });
    
    // Элементы управления
    document.getElementById('perPage').addEventListener('change', function() {
        perPage = parseInt(this.value);
        currentPage = 1;
        updateLaunchesGrid();
    });
}

// Показать уведомление
function showNotification(message, type = 'info') {
    // Удаляем старые уведомления
    const oldNotifications = document.querySelectorAll('.spacex-notification');
    oldNotifications.forEach(n => n.remove());
    
    // Создаем новое уведомление
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} spacex-notification position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white" 
                    onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Автоматически удаляем через 5 секунд
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// CSS для анимации уведомления
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush