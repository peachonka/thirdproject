@extends('layouts.app')

@section('title', 'Астероиды (NeoWs)')

@push('styles')
<style>
    .neo-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 107, 107, 0.3);
        border-radius: 15px;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .neo-card:hover {
        transform: translateY(-5px);
        border-color: #ff6b6b;
        box-shadow: 0 10px 25px rgba(255, 107, 107, 0.2);
    }
    
    .hazardous-badge {
        animation: pulse-danger 2s infinite;
    }
    
    @keyframes pulse-danger {
        0% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 107, 107, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
    }
    
    .size-indicator {
        height: 10px;
        border-radius: 5px;
        background: linear-gradient(90deg, #4cd964 0%, #ff9500 50%, #ff3b30 100%);
        margin: 10px 0;
    }
    
    .approach-table {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .orbit-visualization {
        width: 200px;
        height: 200px;
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        position: relative;
        margin: 20px auto;
    }
    
    .orbit-path {
        width: 100%;
        height: 100%;
        position: absolute;
        border: 1px dashed rgba(255, 255, 255, 0.2);
        border-radius: 50%;
    }
    
    .asteroid-dot {
        width: 10px;
        height: 10px;
        background: #ff6b6b;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        animation: orbit 10s linear infinite;
    }
    
    @keyframes orbit {
        0% { transform: rotate(0deg) translateX(100px) rotate(0deg); }
        100% { transform: rotate(360deg) translateX(100px) rotate(-360deg); }
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <!-- Заголовок и фильтры -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card bg-dark border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mb-0">
                            <i class="fas fa-meteor text-warning me-2"></i>Близкие к Земле объекты (NEO)
                        </h1>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-warning" onclick="refreshData()">
                                <i class="fas fa-sync-alt me-1"></i> Обновить
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showOnlyHazardous()">
                                <i class="fas fa-exclamation-triangle me-1"></i> Только опасные
                            </button>
                        </div>
                    </div>
                    <p class="text-muted mb-0 mt-2">
                        Данные NASA Near Earth Object Web Service (NeoWs). Обновлено: 
                        <span id="lastUpdated">{{ $lastUpdated ?? 'Загрузка...' }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Фильтры -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-filter me-2"></i>Фильтры
                    </h5>
                    <form id="neoFilters" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Период</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="dateFrom" 
                                       value="{{ $filters['date_from'] ?? now()->subDays(7)->format('Y-m-d') }}">
                                <span class="input-group-text">до</span>
                                <input type="date" class="form-control" id="dateTo" 
                                       value="{{ $filters['date_to'] ?? now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Размер (км)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="sizeMin" 
                                       placeholder="Мин" step="0.1" value="0">
                                <span class="input-group-text">-</span>
                                <input type="number" class="form-control" id="sizeMax" 
                                       placeholder="Макс" step="0.1" value="10">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Опасность</label>
                            <select class="form-select" id="hazardousFilter">
                                <option value="all">Все</option>
                                <option value="only">Только опасные</option>
                                <option value="safe">Только безопасные</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Сортировка</label>
                            <select class="form-select" id="sortBy">
                                <option value="date">По дате</option>
                                <option value="size">По размеру</option>
                                <option value="velocity">По скорости</option>
                                <option value="distance">По расстоянию</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">&nbsp;</label>
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-search me-1"></i> Применить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Статистика -->
    <div class="row mb-4 fade-in">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark border-primary">
                <div class="card-body text-center">
                    <h2 class="text-primary" id="totalCount">{{ $stats['total'] ?? 0 }}</h2>
                    <small class="text-muted">Всего астероидов</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark border-danger">
                <div class="card-body text-center">
                    <h2 class="text-danger" id="hazardousCount">{{ $stats['hazardous'] ?? 0 }}</h2>
                    <small class="text-muted">Опасных</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark border-info">
                <div class="card-body text-center">
                    <h2 class="text-info" id="maxDiameter">{{ number_format($stats['max_diameter'] ?? 0, 2) }}</h2>
                    <small class="text-muted">Макс. размер (км)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark border-success">
                <div class="card-body text-center">
                    <h2 class="text-success" id="avgDiameter">{{ number_format($stats['avg_diameter'] ?? 0, 2) }}</h2>
                    <small class="text-muted">Средний размер (км)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark border-warning">
                <div class="card-body text-center">
                    <h2 class="text-warning" id="avgVelocity">{{ number_format($stats['avg_velocity'] ?? 0, 0) }}</h2>
                    <small class="text-muted">Ср. скорость (км/ч)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card bg-dark border-purple">
                <div class="card-body text-center">
                    <h2 class="text-purple" id="hazardousPercentage">{{ number_format($stats['hazardous_percentage'] ?? 0, 1) }}%</h2>
                    <small class="text-muted">Процент опасных</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Список астероидов -->
    <div class="row fade-in">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>
                    <i class="fas fa-list me-2"></i>Список астероидов
                    <span class="badge bg-warning" id="visibleCount">0</span>
                </h3>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm w-auto" id="itemsPerPage">
                        <option value="10">10 на странице</option>
                        <option value="25" selected>25 на странице</option>
                        <option value="50">50 на странице</option>
                        <option value="100">100 на странице</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" onclick="exportToCSV()">
                        <i class="fas fa-download me-1"></i> CSV
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark table-hover" id="neoTable">
                    <thead>
                        <tr>
                            <th width="40"></th>
                            <th>Имя</th>
                            <th>Дата сближения</th>
                            <th>Размер (км)</th>
                            <th>Скорость (км/ч)</th>
                            <th>Расстояние (лд)</th>
                            <th>Опасность</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="neoTableBody">
                        @if(!empty($asteroids) && count($asteroids) > 0)
                            @foreach($asteroids as $asteroid)
                                <tr data-asteroid-id="{{ $asteroid['id'] }}" 
                                    data-hazardous="{{ $asteroid['is_potentially_hazardous_asteroid'] ? 'true' : 'false' }}"
                                    data-size="{{ $asteroid['estimated_diameter']['kilometers']['estimated_diameter_max'] ?? 0 }}">
                                    <td class="text-center">
                                        <i class="fas fa-meteor text-warning"></i>
                                    </td>
                                    <td>
                                        <strong>{{ $asteroid['name'] ?? 'Неизвестно' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $asteroid['id'] }}</small>
                                    </td>
                                    <td>
                                        @if(!empty($asteroid['close_approach_data']))
                                            @php
                                                $approach = $asteroid['close_approach_data'][0];
                                                $date = $approach['close_approach_date'] ?? '';
                                            @endphp
                                            {{ $date }}
                                            <br>
                                            <small class="text-muted">
                                                {{ $approach['close_approach_date_full'] ?? '' }}
                                            </small>
                                        @else
                                            <span class="text-muted">Нет данных</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">
                                                {{ number_format($asteroid['estimated_diameter']['kilometers']['estimated_diameter_max'] ?? 0, 3) }}
                                            </span>
                                            <div class="size-indicator" 
                                                 style="width: {{ min(($asteroid['estimated_diameter']['kilometers']['estimated_diameter_max'] ?? 0) * 20, 100) }}%">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if(!empty($asteroid['close_approach_data']))
                                            @php
                                                $approach = $asteroid['close_approach_data'][0];
                                                $velocity = $approach['relative_velocity']['kilometers_per_hour'] ?? 0;
                                            @endphp
                                            <span class="badge bg-dark">
                                                {{ number_format($velocity, 0) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($asteroid['close_approach_data']))
                                            @php
                                                $approach = $asteroid['close_approach_data'][0];
                                                $distance = $approach['miss_distance']['lunar'] ?? 0;
                                            @endphp
                                            <span class="{{ $distance < 1 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($distance, 2) }}
                                            </span>
                                            <small class="text-muted">лд</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($asteroid['is_potentially_hazardous_asteroid'])
                                            <span class="badge bg-danger hazardous-badge">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Опасен
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i> Безопасен
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="showAsteroidDetails('{{ $asteroid['id'] }}')">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <a href="/neo/{{ $asteroid['id'] }}" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-meteor fa-3x mb-3"></i>
                                        <h4>Нет данных об астероидах</h4>
                                        <p>Попробуйте изменить фильтры или обновить данные</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Показано <span id="showingFrom">1</span>-<span id="showingTo">25</span> из 
                    <span id="totalItems">{{ count($asteroids ?? []) }}</span>
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
    
    <!-- Графики и визуализации -->
    <div class="row fade-in">
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-bar me-2"></i>Распределение по размерам
                    </h5>
                    <canvas id="sizeChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-pie me-2"></i>Соотношение опасных/безопасных
                    </h5>
                    <canvas id="hazardChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-12 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-globe-americas me-2"></i>Ближайшие сближения с Землей
                    </h5>
                    <div class="orbit-visualization">
                        <div class="orbit-path" style="width: 80%; height: 80%; top: 10%; left: 10%;"></div>
                        <div class="orbit-path" style="width: 60%; height: 60%; top: 20%; left: 20%;"></div>
                        <div class="orbit-path" style="width: 40%; height: 40%; top: 30%; left: 30%;"></div>
                        <div class="asteroid-dot"></div>
                        <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <i class="fas fa-earth-americas fa-2x text-info"></i>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted">Визуализация орбит (демо)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно деталей астероида -->
    <div class="modal fade" id="asteroidModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Детали астероида
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="asteroidModalBody">
                    <!-- Контент будет загружен через AJAX -->
                    <div class="text-center py-5">
                        <div class="loading-spinner"></div>
                        <p class="text-muted mt-3">Загрузка данных...</p>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-warning" onclick="openFullDetails()">
                        <i class="fas fa-external-link-alt me-1"></i> Полная информация
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Глобальные переменные
let currentAsteroids = @json($asteroids ?? []);
let currentPage = 1;
let itemsPerPage = 25;
let filteredAsteroids = [];

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    initCharts();
    updateTable();
    setupEventListeners();
    
    // Обновляем статистику
    updateStats();
    
    // Загружаем дополнительные данные если нужно
    if (currentAsteroids.length === 0) {
        loadAsteroidData();
    }
});

// Инициализация фильтров
function initFilters() {
    const form = document.getElementById('neoFilters');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }
    
    // Элементы управления
    document.getElementById('itemsPerPage').addEventListener('change', function() {
        itemsPerPage = parseInt(this.value);
        updateTable();
    });
}

// Применение фильтров
function applyFilters() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const sizeMin = parseFloat(document.getElementById('sizeMin').value) || 0;
    const sizeMax = parseFloat(document.getElementById('sizeMax').value) || 10;
    const hazardousFilter = document.getElementById('hazardousFilter').value;
    const sortBy = document.getElementById('sortBy').value;
    
    // Фильтрация
    filteredAsteroids = currentAsteroids.filter(asteroid => {
        // Фильтр по дате
        let dateMatch = true;
        if (asteroid.close_approach_data && asteroid.close_approach_data[0]) {
            const approachDate = asteroid.close_approach_data[0].close_approach_date;
            if (dateFrom && approachDate < dateFrom) dateMatch = false;
            if (dateTo && approachDate > dateTo) dateMatch = false;
        }
        
        // Фильтр по размеру
        const diameter = asteroid.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
        const sizeMatch = diameter >= sizeMin && diameter <= sizeMax;
        
        // Фильтр по опасности
        let hazardousMatch = true;
        if (hazardousFilter === 'only') {
            hazardousMatch = asteroid.is_potentially_hazardous_asteroid === true;
        } else if (hazardousFilter === 'safe') {
            hazardousMatch = asteroid.is_potentially_hazardous_asteroid === false;
        }
        
        return dateMatch && sizeMatch && hazardousMatch;
    });
    
    // Сортировка
    sortAsteroids(filteredAsteroids, sortBy);
    
    // Обновление UI
    updateTable();
    updateStats();
    updateCharts();
}

// Сортировка астероидов
function sortAsteroids(asteroids, sortBy) {
    asteroids.sort((a, b) => {
        switch(sortBy) {
            case 'size':
                const sizeA = a.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
                const sizeB = b.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
                return sizeB - sizeA;
                
            case 'velocity':
                const velA = a.close_approach_data?.[0]?.relative_velocity?.kilometers_per_hour || 0;
                const velB = b.close_approach_data?.[0]?.relative_velocity?.kilometers_per_hour || 0;
                return velB - velA;
                
            case 'distance':
                const distA = a.close_approach_data?.[0]?.miss_distance?.lunar || Infinity;
                const distB = b.close_approach_data?.[0]?.miss_distance?.lunar || Infinity;
                return distA - distB;
                
            case 'date':
            default:
                const dateA = a.close_approach_data?.[0]?.close_approach_date || '';
                const dateB = b.close_approach_data?.[0]?.close_approach_date || '';
                return dateA.localeCompare(dateB);
        }
    });
}

// Обновление таблицы
function updateTable() {
    const tableBody = document.getElementById('neoTableBody');
    const visibleAsteroids = filteredAsteroids.length > 0 ? filteredAsteroids : currentAsteroids;
    const totalItems = visibleAsteroids.length;
    
    // Пагинация
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    const pageItems = visibleAsteroids.slice(startIndex, endIndex);
    
    // Обновление счетчиков
    document.getElementById('showingFrom').textContent = startIndex + 1;
    document.getElementById('showingTo').textContent = endIndex;
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('visibleCount').textContent = totalItems;
    
    // Очистка таблицы
    tableBody.innerHTML = '';
    
    if (pageItems.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-meteor fa-3x mb-3"></i>
                        <h4>Астероиды не найдены</h4>
                        <p>Попробуйте изменить параметры фильтрации</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    // Заполнение таблицы
    pageItems.forEach(asteroid => {
        const approach = asteroid.close_approach_data?.[0] || {};
        const diameter = asteroid.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
        const velocity = approach.relative_velocity?.kilometers_per_hour || 0;
        const distance = approach.miss_distance?.lunar || 0;
        const isHazardous = asteroid.is_potentially_hazardous_asteroid;
        
        const row = document.createElement('tr');
        row.setAttribute('data-asteroid-id', asteroid.id);
        row.setAttribute('data-hazardous', isHazardous);
        row.setAttribute('data-size', diameter);
        
        row.innerHTML = `
            <td class="text-center">
                <i class="fas fa-meteor text-warning"></i>
            </td>
            <td>
                <strong>${asteroid.name || 'Неизвестно'}</strong>
                <br>
                <small class="text-muted">${asteroid.id}</small>
            </td>
            <td>
                ${approach.close_approach_date || 'Нет данных'}
                <br>
                <small class="text-muted">${approach.close_approach_date_full || ''}</small>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <span class="me-2">${diameter.toFixed(3)}</span>
                    <div class="size-indicator" 
                         style="width: ${Math.min(diameter * 20, 100)}%">
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-dark">${parseInt(velocity).toLocaleString()}</span>
            </td>
            <td>
                <span class="${distance < 1 ? 'text-danger' : 'text-success'}">
                    ${distance.toFixed(2)}
                </span>
                <small class="text-muted">лд</small>
            </td>
            <td>
                ${isHazardous ? 
                    `<span class="badge bg-danger hazardous-badge">
                        <i class="fas fa-exclamation-triangle me-1"></i> Опасен
                    </span>` : 
                    `<span class="badge bg-success">
                        <i class="fas fa-check me-1"></i> Безопасен
                    </span>`}
            </td>
            <td>
                <button class="btn btn-sm btn-outline-info" 
                        onclick="showAsteroidDetails('${asteroid.id}')">
                    <i class="fas fa-info-circle"></i>
                </button>
                <a href="/neo/${asteroid.id}" 
                   class="btn btn-sm btn-outline-warning">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Обновление пагинации
    updatePagination(totalItems);
}

// Обновление пагинации
function updatePagination(totalItems) {
    const pagination = document.getElementById('pagination');
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
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
    if (page < 1 || page > Math.ceil((filteredAsteroids.length || currentAsteroids.length) / itemsPerPage)) {
        return;
    }
    
    currentPage = page;
    updateTable();
}

// Обновление статистики
function updateStats() {
    const visibleAsteroids = filteredAsteroids.length > 0 ? filteredAsteroids : currentAsteroids;
    
    if (visibleAsteroids.length === 0) {
        document.getElementById('totalCount').textContent = '0';
        document.getElementById('hazardousCount').textContent = '0';
        document.getElementById('maxDiameter').textContent = '0.00';
        document.getElementById('avgDiameter').textContent = '0.00';
        document.getElementById('avgVelocity').textContent = '0';
        document.getElementById('hazardousPercentage').textContent = '0.0%';
        return;
    }
    
    let total = visibleAsteroids.length;
    let hazardous = 0;
    let maxDiameter = 0;
    let totalDiameter = 0;
    let totalVelocity = 0;
    let velocityCount = 0;
    
    visibleAsteroids.forEach(asteroid => {
        if (asteroid.is_potentially_hazardous_asteroid) {
            hazardous++;
        }
        
        const diameter = asteroid.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
        maxDiameter = Math.max(maxDiameter, diameter);
        totalDiameter += diameter;
        
        const velocity = asteroid.close_approach_data?.[0]?.relative_velocity?.kilometers_per_hour;
        if (velocity) {
            totalVelocity += parseFloat(velocity);
            velocityCount++;
        }
    });
    
    document.getElementById('totalCount').textContent = total;
    document.getElementById('hazardousCount').textContent = hazardous;
    document.getElementById('maxDiameter').textContent = maxDiameter.toFixed(2);
    document.getElementById('avgDiameter').textContent = (totalDiameter / total).toFixed(2);
    document.getElementById('avgVelocity').textContent = velocityCount > 0 ? 
        parseInt(totalVelocity / velocityCount).toLocaleString() : '0';
    document.getElementById('hazardousPercentage').textContent = 
        ((hazardous / total) * 100).toFixed(1) + '%';
}

// Инициализация графиков
function initCharts() {
    updateCharts();
}

// Обновление графиков
function updateCharts() {
    const visibleAsteroids = filteredAsteroids.length > 0 ? filteredAsteroids : currentAsteroids;
    
    // График распределения по размерам
    const sizeCtx = document.getElementById('sizeChart');
    if (sizeCtx && window.sizeChart) {
        window.sizeChart.destroy();
    }
    
    if (sizeCtx && visibleAsteroids.length > 0) {
        // Группировка по размеру
        const sizeRanges = {
            'Менее 0.1 км': 0,
            '0.1-0.5 км': 0,
            '0.5-1 км': 0,
            '1-5 км': 0,
            'Более 5 км': 0
        };
        
        visibleAsteroids.forEach(asteroid => {
            const diameter = asteroid.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
            
            if (diameter < 0.1) {
                sizeRanges['Менее 0.1 км']++;
            } else if (diameter < 0.5) {
                sizeRanges['0.1-0.5 км']++;
            } else if (diameter < 1) {
                sizeRanges['0.5-1 км']++;
            } else if (diameter < 5) {
                sizeRanges['1-5 км']++;
            } else {
                sizeRanges['Более 5 км']++;
            }
        });
        
        window.sizeChart = new Chart(sizeCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(sizeRanges),
                datasets: [{
                    label: 'Количество астероидов',
                    data: Object.values(sizeRanges),
                    backgroundColor: [
                        'rgba(76, 217, 100, 0.7)',
                        'rgba(90, 200, 250, 0.7)',
                        'rgba(255, 149, 0, 0.7)',
                        'rgba(255, 59, 48, 0.7)',
                        'rgba(255, 45, 85, 0.7)'
                    ],
                    borderColor: [
                        'rgb(76, 217, 100)',
                        'rgb(90, 200, 250)',
                        'rgb(255, 149, 0)',
                        'rgb(255, 59, 48)',
                        'rgb(255, 45, 85)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0c0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#a0a0c0'
                        }
                    }
                }
            }
        });
    }
    
    // График опасных/безопасных
    const hazardCtx = document.getElementById('hazardChart');
    if (hazardCtx && window.hazardChart) {
        window.hazardChart.destroy();
    }
    
    if (hazardCtx && visibleAsteroids.length > 0) {
        let hazardous = 0;
        let safe = 0;
        
        visibleAsteroids.forEach(asteroid => {
            if (asteroid.is_potentially_hazardous_asteroid) {
                hazardous++;
            } else {
                safe++;
            }
        });
        
        window.hazardChart = new Chart(hazardCtx, {
            type: 'doughnut',
            data: {
                labels: ['Опасные', 'Безопасные'],
                datasets: [{
                    data: [hazardous, safe],
                    backgroundColor: [
                        'rgba(255, 59, 48, 0.7)',
                        'rgba(76, 217, 100, 0.7)'
                    ],
                    borderColor: [
                        'rgb(255, 59, 48)',
                        'rgb(76, 217, 100)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#a0a0c0'
                        }
                    }
                }
            }
        });
    }
}

// Показать детали астероида
function showAsteroidDetails(asteroidId) {
    const modal = new bootstrap.Modal(document.getElementById('asteroidModal'));
    const modalBody = document.getElementById('asteroidModalBody');
    
    // Показываем загрузку
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-muted mt-3">Загрузка данных...</p>
        </div>
    `;
    
    modal.show();
    
    // Ищем астероид в данных
    const asteroid = [...currentAsteroids, ...filteredAsteroids].find(a => a.id === asteroidId);
    
    if (asteroid) {
        displayAsteroidDetails(asteroid);
    } else {
        // Загружаем с сервера
        fetch(`/api/neo/${asteroidId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAsteroidDetails(data.asteroid);
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

// Отображение деталей астероида
function displayAsteroidDetails(asteroid) {
    const approach = asteroid.close_approach_data?.[0] || {};
    const diameter = asteroid.estimated_diameter?.kilometers || {};
    const isHazardous = asteroid.is_potentially_hazardous_asteroid;
    
    const modalBody = document.getElementById('asteroidModalBody');
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h4 class="mb-3">${asteroid.name || 'Неизвестный астероид'}</h4>
                <div class="mb-3">
                    <small class="text-muted">ID:</small>
                    <code>${asteroid.id}</code>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="card bg-darker mb-2">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Мин. диаметр</small>
                                <strong>${diameter.estimated_diameter_min?.toFixed(3) || '0.000'} км</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-darker mb-2">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Макс. диаметр</small>
                                <strong>${diameter.estimated_diameter_max?.toFixed(3) || '0.000'} км</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${isHazardous ? `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Потенциально опасный астероид!</strong>
                        <p class="mb-0 small">Этот астероид может представлять угрозу для Земли.</p>
                    </div>
                ` : `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Безопасный астероид</strong>
                        <p class="mb-0 small">Этот астероид не представляет угрозы для Земли.</p>
                    </div>
                `}
                
                ${approach.close_approach_date ? `
                    <h6 class="mt-4 mb-3">Ближайшее сближение</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Дата</small>
                            <strong>${approach.close_approach_date_full || approach.close_approach_date}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Скорость</small>
                            <strong>${parseInt(approach.relative_velocity?.kilometers_per_hour || 0).toLocaleString()} км/ч</strong>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Расстояние</small>
                            <strong>${parseFloat(approach.miss_distance?.lunar || 0).toFixed(2)} лд</strong>
                            <br>
                            <small class="text-muted">(${parseFloat(approach.miss_distance?.kilometers || 0).toLocaleString()} км)</small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Орбитальное тело</small>
                            <strong>${approach.orbiting_body || 'Земля'}</strong>
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="orbit-visualization mb-3">
                        <div class="orbit-path" style="width: 80%; height: 80%; top: 10%; left: 10%;"></div>
                        <div class="orbit-path" style="width: 60%; height: 60%; top: 20%; left: 20%;"></div>
                        <div class="orbit-path" style="width: 40%; height: 40%; top: 30%; left: 30%;"></div>
                        <div class="asteroid-dot"></div>
                        <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <i class="fas fa-earth-americas fa-2x text-info"></i>
                        </div>
                    </div>
                    <small class="text-muted">Относительная орбита</small>
                </div>
                
                <div class="mt-4">
                    <h6>Дополнительно</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="fas fa-link me-2 text-muted"></i>
                            <a href="${asteroid.nasa_jpl_url || '#'}" target="_blank" class="text-info">
                                Страница на NASA JPL
                            </a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-chart-line me-2 text-muted"></i>
                            Абсолютная магнитуда: <strong>${asteroid.absolute_magnitude_h?.toFixed(1) || '—'}</strong>
                        </li>
                        <li>
                            <i class="fas fa-sync-alt me-2 text-muted"></i>
                            Последнее обновление: <strong>${new Date().toLocaleDateString()}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    `;
}

// Открыть полную информацию
function openFullDetails() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('asteroidModal'));
    if (modal) modal.hide();
    
    // Перенаправляем на страницу деталей
    // Здесь должен быть код для получения ID текущего астероида
    // Например: window.location.href = `/neo/${currentAsteroidId}`;
}

// Показать только опасные астероиды
function showOnlyHazardous() {
    document.getElementById('hazardousFilter').value = 'only';
    applyFilters();
}

// Экспорт в CSV
function exportToCSV() {
    const visibleAsteroids = filteredAsteroids.length > 0 ? filteredAsteroids : currentAsteroids;
    
    if (visibleAsteroids.length === 0) {
        alert('Нет данных для экспорта');
        return;
    }
    
    // Создаем CSV заголовок
    let csv = 'Имя,ID,Дата сближения,Размер (км),Скорость (км/ч),Расстояние (лд),Опасность\n';
    
    // Добавляем данные
    visibleAsteroids.forEach(asteroid => {
        const approach = asteroid.close_approach_data?.[0] || {};
        const diameter = asteroid.estimated_diameter?.kilometers?.estimated_diameter_max || 0;
        const velocity = approach.relative_velocity?.kilometers_per_hour || 0;
        const distance = approach.miss_distance?.lunar || 0;
        const isHazardous = asteroid.is_potentially_hazardous_asteroid ? 'Да' : 'Нет';
        
        csv += `"${asteroid.name || 'Неизвестно'}","${asteroid.id}","${approach.close_approach_date || ''}",`;
        csv += `${diameter.toFixed(3)},${parseInt(velocity)},${distance.toFixed(2)},${isHazardous}\n`;
    });
    
    // Создаем Blob и скачиваем
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `asteroids_${new Date().toISOString().slice(0, 10)}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Обновление данных
function refreshData() {
    loadAsteroidData();
    showNotification('Обновление данных...', 'info');
}

// Загрузка данных с сервера
function loadAsteroidData() {
    const filters = {
        date_from: document.getElementById('dateFrom').value,
        date_to: document.getElementById('dateTo').value,
        size_min: document.getElementById('sizeMin').value,
        size_max: document.getElementById('sizeMax').value,
        hazardous: document.getElementById('hazardousFilter').value,
        sort_by: document.getElementById('sortBy').value
    };
    
    fetch('/api/neo/filter', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(filters)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentAsteroids = data.asteroids || [];
            filteredAsteroids = [];
            currentPage = 1;
            
            updateTable();
            updateStats();
            updateCharts();
            
            document.getElementById('lastUpdated').textContent = new Date().toLocaleString();
            showNotification('Данные обновлены', 'success');
        } else {
            showNotification('Ошибка обновления данных', 'danger');
        }
    })
    .catch(error => {
        console.error('Ошибка загрузки данных:', error);
        showNotification('Ошибка загрузки данных', 'danger');
    });
}

// Настройка обработчиков событий
function setupEventListeners() {
    // Обновление данных при изменении фильтров
    ['dateFrom', 'dateTo', 'sizeMin', 'sizeMax', 'hazardousFilter', 'sortBy'].forEach(id => {
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
}

// Показать уведомление
function showNotification(message, type = 'info') {
    // Удаляем старые уведомления
    const oldNotifications = document.querySelectorAll('.custom-notification');
    oldNotifications.forEach(n => n.remove());
    
    // Создаем новое уведомление
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} custom-notification position-fixed`;
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