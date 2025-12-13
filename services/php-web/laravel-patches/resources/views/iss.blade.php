@extends('layouts.app')

@section('title', 'МКС - Трекинг в реальном времени')

@section('styles')
<style>
    #issMap {
        height: 500px;
        border-radius: 15px;
        border: 2px solid rgba(108, 99, 255, 0.3);
        margin-bottom: 20px;
    }
    
    .iss-marker {
        background: #6c63ff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        border: 3px solid white;
        box-shadow: 0 0 10px rgba(108, 99, 255, 0.8);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(108, 99, 255, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(108, 99, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(108, 99, 255, 0); }
    }
    
    .connection-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .status-connected {
        background: rgba(0, 255, 0, 0.1);
        color: #00ff00;
        border: 1px solid #00ff00;
    }
    
    .status-disconnected {
        background: rgba(255, 0, 0, 0.1);
        color: #ff0000;
        border: 1px solid #ff0000;
    }
    
    .status-connecting {
        background: rgba(255, 165, 0, 0.1);
        color: #ffa500;
        border: 1px solid #ffa500;
    }
    
    .realtime-badge {
        animation: glow 2s infinite alternate;
    }
    
    @keyframes glow {
        from { box-shadow: 0 0 5px #6c63ff; }
        to { box-shadow: 0 0 15px #6c63ff; }
    }
</style>
@endsection

@section('content')
<div class="container py-4 fade-in">
    <!-- Заголовок с статусом подключения -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>
            <i class="fas fa-satellite me-2"></i>
            МКС - Трекинг в реальном времени
            <span class="badge bg-primary realtime-badge ms-2">LIVE</span>
        </h3>
        <div>
            <span id="connectionStatus" class="connection-status status-disconnected">
                <i class="fas fa-plug"></i>
                <span>Отключено</span>
            </span>
            <button id="toggleWs" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-play me-1"></i>Подключить LIVE
            </button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Левая колонка: карта и управление -->
        <div class="col-lg-8">
            <!-- Карта -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-map me-2"></i>Текущая позиция на карте
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="issMap"></div>
                </div>
                <div class="card-footer bg-dark">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-light" id="centerMap">
                            <i class="fas fa-crosshairs me-1"></i>Центрировать
                        </button>
                        <button class="btn btn-sm btn-outline-light" id="togglePath">
                            <i class="fas fa-route me-1"></i>Показать путь
                        </button>
                        <button class="btn btn-sm btn-outline-light" id="refreshData">
                            <i class="fas fa-sync-alt me-1"></i>Обновить вручную
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- История пути (график) -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>История движения (последние 10 позиций)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover" id="positionHistory">
                            <thead>
                                <tr>
                                    <th>Время</th>
                                    <th>Широта</th>
                                    <th>Долгота</th>
                                    <th>Высота (км)</th>
                                    <th>Скорость (км/ч)</th>
                                </tr>
                            </thead>
                            <tbody id="historyBody">
                                <!-- Заполняется через JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Правая колонка: данные и управление -->
        <div class="col-lg-4">
            <!-- Текущие данные -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Текущие данные
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item bg-transparent border-secondary">
                            <div class="d-flex justify-content-between">
                                <span>Широта:</span>
                                <strong id="currentLat">—</strong>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-secondary">
                            <div class="d-flex justify-content-between">
                                <span>Долгота:</span>
                                <strong id="currentLng">—</strong>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-secondary">
                            <div class="d-flex justify-content-between">
                                <span>Высота:</span>
                                <strong id="currentAlt">— км</strong>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-secondary">
                            <div class="d-flex justify-content-between">
                                <span>Скорость:</span>
                                <strong id="currentVel">— км/ч</strong>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-secondary">
                            <div class="d-flex justify-content-between">
                                <span>Видимость:</span>
                                <strong id="currentVis">—</strong>
                            </div>
                        </div>
                        <div class="list-group-item bg-transparent border-secondary">
                            <div class="d-flex justify-content-between">
                                <span>Последнее обновление:</span>
                                <strong id="lastUpdate">—</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Существующие данные (из контроллера) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">Последний запрос API</h5>
                </div>
                <div class="card-body">
                    @if(!empty($last['payload']))
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent border-secondary">
                                Широта: {{ $last['payload']['latitude'] ?? '—' }}
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Долгота: {{ $last['payload']['longitude'] ?? '—' }}
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Высота: {{ $last['payload']['altitude'] ?? '—' }} км
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Скорость: {{ $last['payload']['velocity'] ?? '—' }} км/ч
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Время: {{ $last['fetched_at'] ?? '—' }}
                            </li>
                        </ul>
                    @else
                        <div class="text-muted">нет данных</div>
                    @endif
                    <div class="mt-3">
                        <code class="small">{{ $base }}/last</code>
                    </div>
                </div>
            </div>

            <!-- Тренд движения -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark">
                    <h5 class="mb-0">Тренд движения</h5>
                </div>
                <div class="card-body">
                    @if(!empty($trend))
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent border-secondary">
                                Движение: {{ ($trend['movement'] ?? false) ? 'Да' : 'Нет' }}
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Смещение: {{ number_format($trend['delta_km'] ?? 0, 3, '.', ' ') }} км
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Интервал: {{ $trend['dt_sec'] ?? 0 }} сек
                            </li>
                            <li class="list-group-item bg-transparent border-secondary">
                                Скорость: {{ $trend['velocity_kmh'] ?? '—' }} км/ч
                            </li>
                        </ul>
                    @else
                        <div class="text-muted">нет данных</div>
                    @endif
                    <div class="mt-3">
                        <code class="small">{{ $base }}/iss/trend</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- WebSocket клиент -->
<script src="{{ asset('js/websocket.js') }}"></script>

<script>
// Глобальные переменные
let issMap = null;
let issMarker = null;
let issPath = [];
let pathLayer = null;
let positionHistory = [];
const MAX_HISTORY = 10;

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    setupWebSocket();
    setupEventListeners();
    
    // Загружаем начальные данные
    loadInitialData();
});

// Инициализация карты
function initMap() {
    issMap = L.map('issMap').setView([0, 0], 2);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(issMap);
    
    // Создаем маркер ISS
    issMarker = L.marker([0, 0], {
        icon: L.divIcon({
            className: 'iss-marker',
            html: '<i class="fas fa-satellite"></i>',
            iconSize: [30, 30]
        })
    }).addTo(issMap);
    
    // Слой для пути
    pathLayer = L.layerGroup().addTo(issMap);
}

// Настройка WebSocket
function setupWebSocket() {
    const ws = window.issWebSocket;
    
    ws.onUpdate(function(data) {
        console.log('ISS Update received:', data);
        updateDisplay(data);
        updateMap(data.latitude, data.longitude);
        addToHistory(data);
    });
    
    ws.onConnect(function() {
        updateConnectionStatus('connected');
        showToast('Подключено к реальному трекеру МКС', 'success');
    });
    
    ws.onDisconnect(function() {
        updateConnectionStatus('disconnected');
        showToast('Соединение с трекером потеряно', 'warning');
    });
}

// Обновление статуса подключения
function updateConnectionStatus(status) {
    const element = document.getElementById('connectionStatus');
    const button = document.getElementById('toggleWs');
    
    element.className = `connection-status status-${status}`;
    
    switch(status) {
        case 'connected':
            element.innerHTML = '<i class="fas fa-plug"></i><span>Подключено</span>';
            button.innerHTML = '<i class="fas fa-stop me-1"></i>Отключить LIVE';
            button.classList.remove('btn-primary');
            button.classList.add('btn-danger');
            break;
        case 'connecting':
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Подключение...</span>';
            button.innerHTML = '<i class="fas fa-stop me-1"></i>Отмена';
            button.classList.remove('btn-primary');
            button.classList.add('btn-warning');
            break;
        case 'disconnected':
            element.innerHTML = '<i class="fas fa-plug"></i><span>Отключено</span>';
            button.innerHTML = '<i class="fas fa-play me-1"></i>Подключить LIVE';
            button.classList.remove('btn-danger', 'btn-warning');
            button.classList.add('btn-primary');
            break;
    }
}

// Обновление отображения данных
function updateDisplay(data) {
    document.getElementById('currentLat').textContent = data.latitude?.toFixed(4) || '—';
    document.getElementById('currentLng').textContent = data.longitude?.toFixed(4) || '—';
    document.getElementById('currentAlt').textContent = (data.altitude ? data.altitude.toFixed(2) + ' км' : '—');
    document.getElementById('currentVel').textContent = (data.velocity ? data.velocity.toFixed(2) + ' км/ч' : '—');
    document.getElementById('currentVis').textContent = data.visibility || '—';
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
}

// Обновление карты
function updateMap(lat, lng) {
    if (lat && lng) {
        issMarker.setLatLng([lat, lng]);
        
        // Добавляем точку в путь
        issPath.push([lat, lng]);
        if (issPath.length > 50) issPath.shift();
        
        // Обновляем линию пути
        updatePath();
    }
}

// Обновление линии пути
function updatePath() {
    pathLayer.clearLayers();
    if (issPath.length > 1) {
        L.polyline(issPath, {
            color: '#6c63ff',
            weight: 2,
            opacity: 0.7,
            dashArray: '5, 10'
        }).addTo(pathLayer);
    }
}

// Добавление в историю
function addToHistory(data) {
    positionHistory.unshift({
        time: new Date().toLocaleTimeString(),
        lat: data.latitude?.toFixed(4) || '—',
        lng: data.longitude?.toFixed(4) || '—',
        alt: data.altitude?.toFixed(2) || '—',
        vel: data.velocity?.toFixed(2) || '—'
    });
    
    if (positionHistory.length > MAX_HISTORY) {
        positionHistory.pop();
    }
    
    updateHistoryTable();
}

// Обновление таблицы истории
function updateHistoryTable() {
    const tbody = document.getElementById('historyBody');
    tbody.innerHTML = '';
    
    positionHistory.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.time}</td>
            <td>${item.lat}</td>
            <td>${item.lng}</td>
            <td>${item.alt}</td>
            <td>${item.vel}</td>
        `;
        tbody.appendChild(row);
    });
}

// Настройка обработчиков событий
function setupEventListeners() {
    document.getElementById('toggleWs').addEventListener('click', function() {
        if (window.issWebSocket.ws?.readyState === WebSocket.OPEN) {
            window.issWebSocket.disconnect();
        } else {
            window.issWebSocket.connect();
            updateConnectionStatus('connecting');
        }
    });
    
    document.getElementById('centerMap').addEventListener('click', function() {
        if (issPath.length > 0) {
            const lastPos = issPath[issPath.length - 1];
            issMap.setView(lastPos, issMap.getZoom());
        }
    });
    
    document.getElementById('togglePath').addEventListener('click', function() {
        if (pathLayer.hasLayer()) {
            issMap.removeLayer(pathLayer);
            this.innerHTML = '<i class="fas fa-route me-1"></i>Показать путь';
        } else {
            pathLayer.addTo(issMap);
            updatePath();
            this.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Скрыть путь';
        }
    });
    
    document.getElementById('refreshData').addEventListener('click', loadInitialData);
}

// Загрузка начальных данных
function loadInitialData() {
    fetch('/api/rust/iss/current')
        .then(response => response.json())
        .then(data => {
            if (data.payload) {
                updateDisplay({
                    latitude: data.payload.latitude,
                    longitude: data.payload.longitude,
                    altitude: data.payload.altitude,
                    velocity: data.payload.velocity,
                    visibility: data.payload.visibility
                });
                updateMap(data.payload.latitude, data.payload.longitude);
            }
        })
        .catch(error => console.error('Error loading ISS data:', error));
}

// Вспомогательная функция для уведомлений
function showToast(message, type = 'info') {
    // Можно подключить Toast библиотеку или сделать простой alert
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endpush