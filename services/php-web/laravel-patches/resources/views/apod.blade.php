@extends('layouts.app')

@section('title', 'Astronomy Picture of the Day')

@push('styles')
<style>
    .apod-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(0, 180, 216, 0.3);
        border-radius: 15px;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .apod-card:hover {
        transform: translateY(-5px);
        border-color: #00b4d8;
        box-shadow: 0 15px 30px rgba(0, 180, 216, 0.2);
    }
    
    .apod-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .apod-image:hover {
        transform: scale(1.05);
    }
    
    .image-container {
        position: relative;
        overflow: hidden;
        border-radius: 10px 10px 0 0;
    }
    
    .image-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        padding: 20px;
        color: white;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .image-container:hover .image-overlay {
        opacity: 1;
    }
    
    .date-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        z-index: 10;
    }
    
    .video-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
        height: 0;
        overflow: hidden;
    }
    
    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }
    
    .archive-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .archive-item {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .archive-item:hover {
        transform: scale(1.05);
    }
    
    .lightbox {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }
    
    .lightbox img {
        max-width: 90%;
        max-height: 90%;
        border-radius: 10px;
    }
    
    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        z-index: 10000;
    }
    
    .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: white;
        font-size: 3rem;
        cursor: pointer;
        z-index: 10000;
        opacity: 0.7;
        transition: opacity 0.3s ease;
    }
    
    .lightbox-nav:hover {
        opacity: 1;
    }
    
    .lightbox-prev {
        left: 20px;
    }
    
    .lightbox-next {
        right: 20px;
    }
    
    .favorite-btn {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 10;
        background: rgba(0, 0, 0, 0.7);
        border: none;
        color: #ffd700;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .favorite-btn:hover {
        background: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
    }
    
    .favorite-btn.active {
        background: rgba(255, 215, 0, 0.2);
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255, 255, 255, 0.1);
        border-top: 5px solid #00b4d8;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 50px auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <!-- Заголовок и поиск -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card bg-dark border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mb-0">
                            <i class="fas fa-image text-info me-2"></i>Astronomy Picture of the Day
                        </h1>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-info" onclick="showRandomApod()">
                                <i class="fas fa-random me-1"></i> Случайная
                            </button>
                            <button class="btn btn-sm btn-info" onclick="showTodayApod()">
                                <i class="fas fa-calendar-day me-1"></i> Сегодня
                            </button>
                        </div>
                    </div>
                    <p class="text-muted mb-0 mt-2">
                        Ежедневная фотография от NASA. Каждый день новое изображение нашей удивительной вселенной.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Основное изображение дня -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card apod-card" id="mainApodCard">
                <div class="card-body p-0">
                    <!-- Дата -->
                    <div class="date-badge" id="apodDate">
                        {{ now()->format('d.m.Y') }}
                    </div>
                    
                    <!-- Кнопка избранного -->
                    <button class="favorite-btn" id="favoriteBtn" onclick="toggleFavorite()">
                        <i class="far fa-star"></i>
                    </button>
                    
                    <!-- Контейнер для изображения/видео -->
                    <div id="apodMediaContainer">
                        <div class="text-center py-5">
                            <div class="loading-spinner"></div>
                            <p class="text-muted mt-3">Загрузка картины дня...</p>
                        </div>
                    </div>
                    
                    <!-- Информация -->
                    <div class="p-4">
                        <h2 id="apodTitle" class="mb-3">Загрузка...</h2>
                        <p id="apodExplanation" class="text-muted">
                            Загрузка описания...
                        </p>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Автор</small>
                                    <strong id="apodCopyright">NASA</strong>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Тип медиа</small>
                                    <span class="badge bg-info" id="apodMediaType">image</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Ссылки</small>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="btn btn-sm btn-outline-info" id="hdUrlLink" target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i> HD версия
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="downloadApod()">
                                            <i class="fas fa-download me-1"></i> Скачать
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Фильтры архива -->
    <div class="row mb-4 fade-in">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-archive me-2"></i>Архив APOD
                    </h5>
                    
                    <form id="archiveFilters" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Период</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="archiveFrom" 
                                       value="{{ now()->subDays(30)->format('Y-m-d') }}">
                                <span class="input-group-text">до</span>
                                <input type="date" class="form-control" id="archiveTo" 
                                       value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Тип</label>
                            <select class="form-select" id="mediaTypeFilter">
                                <option value="all">Все</option>
                                <option value="image">Изображения</option>
                                <option value="video">Видео</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Поиск</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchQuery" 
                                       placeholder="Поиск по названию или описанию...">
                                <button class="btn btn-outline-info" type="button" onclick="searchApod()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">&nbsp;</label>
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-filter me-1"></i> Поиск в архиве
                            </button>
                        </div>
                    </form>
                    
                    <!-- Быстрый выбор -->
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2">Быстрый выбор:</small>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="setDateRange('week')">
                                                Последняя неделя
                                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="setDateRange('month')">
                                Последний месяц
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="setDateRange('year')">
                                Последний год
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="showFavorites()">
                                <i class="fas fa-star me-1"></i> Избранное
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Архив -->
    <div class="row fade-in">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>
                    <i class="fas fa-images me-2"></i>Архив изображений
                    <span class="badge bg-info" id="archiveCount">0</span>
                </h3>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm w-auto" id="archivePerPage">
                        <option value="12">12 на странице</option>
                        <option value="24" selected>24 на странице</option>
                        <option value="48">48 на странице</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" onclick="exportArchive()">
                        <i class="fas fa-download me-1"></i> Экспорт
                    </button>
                </div>
            </div>
            
            <!-- Результаты поиска -->
            <div id="searchResults" class="mb-4" style="display: none;">
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-search me-2"></i>
                            Результаты поиска: <strong id="searchQueryText"></strong>
                            (<span id="searchCount">0</span> найденных)
                        </div>
                        <button class="btn btn-sm btn-outline-info" onclick="clearSearch()">
                            <i class="fas fa-times me-1"></i> Очистить
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Сетка архива -->
            <div class="archive-grid" id="archiveGrid">
                <!-- Архив будет загружен через JavaScript -->
                <div class="col-12 text-center py-5">
                    <div class="loading-spinner"></div>
                    <p class="text-muted mt-3">Загрузка архива...</p>
                </div>
            </div>
            
            <!-- Пагинация -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    Показано <span id="archiveFrom">1</span>-<span id="archiveTo">24</span> из 
                    <span id="archiveTotal">0</span>
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="archivePagination">
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
    
    <!-- Статистика -->
    <div class="row fade-in">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Статистика APOD
                    </h5>
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center">
                                <h2 class="text-info" id="totalImages">0</h2>
                                <small class="text-muted">Всего изображений</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center">
                                <h2 class="text-warning" id="totalVideos">0</h2>
                                <small class="text-muted">Всего видео</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center">
                                <h2 class="text-success" id="favoritesCount">0</h2>
                                <small class="text-muted">В избранном</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center">
                                <h2 class="text-primary" id="daysCovered">0</h2>
                                <small class="text-muted">Дней покрыто</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Лайтбокс для полноэкранного просмотра -->
<div class="lightbox" id="lightbox">
    <div class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </div>
    <div class="lightbox-nav lightbox-prev" onclick="prevLightbox()">
        <i class="fas fa-chevron-left"></i>
    </div>
    <div class="lightbox-nav lightbox-next" onclick="nextLightbox()">
        <i class="fas fa-chevron-right"></i>
    </div>
    <img id="lightboxImage" src="" alt="">
    <div class="position-absolute bottom-0 start-0 end-0 text-center text-white p-3">
        <div id="lightboxTitle"></div>
        <small id="lightboxDate"></small>
    </div>
</div>

<!-- Модальное окно информации -->
<div class="modal fade" id="apodInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-info">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Подробная информация
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="apodInfoContent">
                <!-- Контент будет загружен динамически -->
            </div>
            <div class="modal-footer border-info">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-info" onclick="openInNewTab()">
                    <i class="fas fa-external-link-alt me-1"></i> Открыть на сайте NASA
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Глобальные переменные
let currentApod = null;
let archiveData = [];
let filteredArchive = [];
let searchResults = [];
let favorites = JSON.parse(localStorage.getItem('apod_favorites') || '{}');
let lightboxImages = [];
let lightboxIndex = 0;
let currentArchivePage = 1;
let archivePerPage = 24;

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    loadTodayApod();
    loadArchive();
    setupEventListeners();
    updateStats();
    updateFavoritesCount();
    
    // Восстановить избранное
    updateFavoriteButton();
});

// Загрузка сегодняшнего APOD
function loadTodayApod() {
    const mediaContainer = document.getElementById('apodMediaContainer');
    mediaContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-muted mt-3">Загрузка картины дня...</p>
        </div>
    `;
    
    fetch('/api/apod/latest')
        .then(response => response.json())
        .then(data => {
            displayApod(data);
            currentApod = data;
            updateFavoriteButton();
        })
        .catch(error => {
            console.error('Ошибка загрузки APOD:', error);
            mediaContainer.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Ошибка загрузки</h4>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Загрузка APOD по дате
function loadApodByDate(date) {
    const mediaContainer = document.getElementById('apodMediaContainer');
    mediaContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-muted mt-3">Загрузка...</p>
        </div>
    `;
    
    fetch(`/api/apod?date=${date}`)
        .then(response => response.json())
        .then(data => {
            displayApod(data);
            currentApod = data;
            updateFavoriteButton();
        })
        .catch(error => {
            console.error('Ошибка загрузки APOD:', error);
            mediaContainer.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Ошибка загрузки</h4>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Отображение APOD
function displayApod(apod) {
    const mediaContainer = document.getElementById('apodMediaContainer');
    const titleElement = document.getElementById('apodTitle');
    const explanationElement = document.getElementById('apodExplanation');
    const copyrightElement = document.getElementById('apodCopyright');
    const mediaTypeElement = document.getElementById('apodMediaType');
    const dateElement = document.getElementById('apodDate');
    const hdUrlLink = document.getElementById('hdUrlLink');
    
    // Обновляем информацию
    titleElement.textContent = apod.title || 'Astronomy Picture of the Day';
    explanationElement.textContent = apod.explanation || 'Описание отсутствует';
    copyrightElement.textContent = apod.copyright || 'NASA';
    mediaTypeElement.textContent = apod.media_type || 'image';
    dateElement.textContent = apod.date ? new Date(apod.date).toLocaleDateString() : new Date().toLocaleDateString();
    
    // Настраиваем ссылку HD
    if (apod.hdurl) {
        hdUrlLink.href = apod.hdurl;
        hdUrlLink.style.display = 'block';
    } else {
        hdUrlLink.style.display = 'none';
    }
    
    // Отображаем медиа
    if (apod.media_type === 'video') {
        // Видео
        mediaContainer.innerHTML = `
            <div class="video-container">
                <iframe src="${apod.url}" 
                        allowfullscreen 
                        frameborder="0">
                </iframe>
            </div>
        `;
    } else {
        // Изображение
        mediaContainer.innerHTML = `
            <div class="image-container">
                <img src="${apod.url}" 
                     alt="${apod.title}" 
                     class="apod-image"
                     onclick="openLightbox('${apod.url}', '${apod.title}', '${apod.date}')">
                <div class="image-overlay">
                    <h5>${apod.title}</h5>
                    <small>${apod.date} • ${apod.copyright || 'NASA'}</small>
                </div>
            </div>
        `;
    }
}

// Загрузка архива
function loadArchive() {
    const archiveGrid = document.getElementById('archiveGrid');
    archiveGrid.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-muted mt-3">Загрузка архива...</p>
        </div>
    `;
    
    const from = document.getElementById('archiveFrom').value;
    const to = document.getElementById('archiveTo').value;
    
    fetch(`/api/apod/archive?start_date=${from}&end_date=${to}`)
        .then(response => response.json())
        .then(data => {
            archiveData = data.items || [];
            filteredArchive = [...archiveData];
            updateArchiveGrid();
            updateArchiveStats();
        })
        .catch(error => {
            console.error('Ошибка загрузки архива:', error);
            archiveGrid.innerHTML = `
                <div class="col-12 text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Ошибка загрузки архива</h4>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// Обновление сетки архива
function updateArchiveGrid() {
    const archiveGrid = document.getElementById('archiveGrid');
    const totalItems = filteredArchive.length;
    
    // Пагинация
    const startIndex = (currentArchivePage - 1) * archivePerPage;
    const endIndex = Math.min(startIndex + archivePerPage, totalItems);
    const pageItems = filteredArchive.slice(startIndex, endIndex);
    
    // Обновление счетчиков
    document.getElementById('archiveFrom').textContent = startIndex + 1;
    document.getElementById('archiveTo').textContent = endIndex;
    document.getElementById('archiveTotal').textContent = totalItems;
    document.getElementById('archiveCount').textContent = totalItems;
    
    // Очистка сетки
    archiveGrid.innerHTML = '';
    
    if (pageItems.length === 0) {
        archiveGrid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-image fa-3x mb-3"></i>
                    <h4>Архив пуст</h4>
                    <p>Попробуйте изменить параметры фильтрации</p>
                </div>
            </div>
        `;
        updateArchivePagination(totalItems);
        return;
    }
    
    // Заполнение сетки
    pageItems.forEach(item => {
        const isFavorite = favorites[item.date] || false;
        const isImage = item.media_type === 'image';
        
        const archiveItem = document.createElement('div');
        archiveItem.className = 'archive-item';
        archiveItem.innerHTML = `
            <div class="card bg-dark border-secondary h-100">
                ${isImage ? `
                    <img src="${item.url || item.thumbnail}" 
                         class="card-img-top" 
                         alt="${item.title}"
                         style="height: 150px; object-fit: cover;"
                         onclick="openLightbox('${item.url || item.hdurl}', '${item.title}', '${item.date}')">
                ` : `
                    <div class="text-center py-4">
                        <i class="fas fa-video fa-3x text-warning"></i>
                        <p class="small mt-2">Видео</p>
                    </div>
                `}
                <div class="card-body">
                    <h6 class="card-title" style="font-size: 0.9rem; height: 40px; overflow: hidden;">
                        ${item.title || 'APOD'}
                    </h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${item.date || ''}</small>
                        <div>
                            <button class="btn btn-sm btn-outline-${isImage ? 'info' : 'warning'}" 
                                    onclick="viewApodDetails('${item.date}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm ${isFavorite ? 'btn-warning' : 'btn-outline-warning'}" 
                                    onclick="toggleArchiveFavorite('${item.date}', this)">
                                <i class="${isFavorite ? 'fas' : 'far'} fa-star"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        archiveGrid.appendChild(archiveItem);
    });
    
    // Обновление лайтбокс изображений
    lightboxImages = pageItems.filter(item => item.media_type === 'image')
        .map(item => ({
            url: item.url || item.hdurl,
            title: item.title,
            date: item.date
        }));
    
    updateArchivePagination(totalItems);
}

// Обновление пагинации архива
function updateArchivePagination(totalItems) {
    const pagination = document.getElementById('archivePagination');
    const totalPages = Math.ceil(totalItems / archivePerPage);
    
    // Очистка пагинации
    pagination.innerHTML = '';
    
    // Кнопка "Назад"
    const prevButton = document.createElement('li');
    prevButton.className = `page-item ${currentArchivePage === 1 ? 'disabled' : ''}`;
    prevButton.innerHTML = `
        <a class="page-link" href="#" onclick="changeArchivePage(${currentArchivePage - 1})" tabindex="-1">
            <i class="fas fa-chevron-left"></i>
        </a>
    `;
    pagination.appendChild(prevButton);
    
    // Номера страниц
    const startPage = Math.max(1, currentArchivePage - 2);
    const endPage = Math.min(totalPages, currentArchivePage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === currentArchivePage ? 'active' : ''}`;
        pageItem.innerHTML = `
            <a class="page-link" href="#" onclick="changeArchivePage(${i})">${i}</a>
        `;
        pagination.appendChild(pageItem);
    }
    
    // Кнопка "Вперед"
    const nextButton = document.createElement('li');
    nextButton.className = `page-item ${currentArchivePage === totalPages ? 'disabled' : ''}`;
    nextButton.innerHTML = `
        <a class="page-link" href="#" onclick="changeArchivePage(${currentArchivePage + 1})">
            <i class="fas fa-chevron-right"></i>
        </a>
    `;
    pagination.appendChild(nextButton);
}

// Смена страницы архива
function changeArchivePage(page) {
    const totalPages = Math.ceil(filteredArchive.length / archivePerPage);
    
    if (page < 1 || page > totalPages) {
        return;
    }
    
    currentArchivePage = page;
    updateArchiveGrid();
}

// Поиск в архиве
function searchApod() {
    const query = document.getElementById('searchQuery').value.toLowerCase().trim();
    
    if (!query) {
        clearSearch();
        return;
    }
    
    searchResults = archiveData.filter(item => {
        const title = item.title?.toLowerCase() || '';
        const explanation = item.explanation?.toLowerCase() || '';
        return title.includes(query) || explanation.includes(query);
    });
    
    // Показываем результаты поиска
    document.getElementById('searchResults').style.display = 'block';
    document.getElementById('searchQueryText').textContent = query;
    document.getElementById('searchCount').textContent = searchResults.length;
    
    // Обновляем отображаемые данные
    filteredArchive = searchResults;
    currentArchivePage = 1;
    updateArchiveGrid();
}

// Очистка поиска
function clearSearch() {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchQuery').value = '';
    filteredArchive = [...archiveData];
    searchResults = [];
    currentArchivePage = 1;
    updateArchiveGrid();
}

// Настройка периода
function setDateRange(range) {
    const today = new Date();
    const from = document.getElementById('archiveFrom');
    const to = document.getElementById('archiveTo');
    
    let startDate = new Date();
    
    switch(range) {
        case 'week':
            startDate.setDate(today.getDate() - 7);
            break;
        case 'month':
            startDate.setMonth(today.getMonth() - 1);
            break;
        case 'year':
            startDate.setFullYear(today.getFullYear() - 1);
            break;
    }
    
    from.value = startDate.toISOString().split('T')[0];
    to.value = today.toISOString().split('T')[0];
    
    // Применяем фильтры
    applyArchiveFilters();
}

// Применение фильтров архива
function applyArchiveFilters() {
    const from = document.getElementById('archiveFrom').value;
    const to = document.getElementById('archiveTo').value;
    const mediaType = document.getElementById('mediaTypeFilter').value;
    
    filteredArchive = archiveData.filter(item => {
        // Фильтр по дате
        const dateMatch = (!from || item.date >= from) && (!to || item.date <= to);
        
        // Фильтр по типу медиа
        const typeMatch = mediaType === 'all' || item.media_type === mediaType;
        
        return dateMatch && typeMatch;
    });
    
    currentArchivePage = 1;
    updateArchiveGrid();
}

// Показать избранное
function showFavorites() {
    const favoriteDates = Object.keys(favorites).filter(date => favorites[date]);
    filteredArchive = archiveData.filter(item => favoriteDates.includes(item.date));
    
    // Показываем сообщение если нет избранного
    if (filteredArchive.length === 0) {
        showNotification('У вас пока нет избранных APOD', 'warning');
        filteredArchive = [...archiveData];
    }
    
    currentArchivePage = 1;
    updateArchiveGrid();
}

// Показать случайный APOD
function showRandomApod() {
    if (archiveData.length === 0) {
        showNotification('Сначала загрузите архив', 'warning');
        return;
    }
    
    const randomIndex = Math.floor(Math.random() * archiveData.length);
    const randomApod = archiveData[randomIndex];
    
    loadApodByDate(randomApod.date);
    showNotification(`Загружен APOD от ${randomApod.date}`, 'info');
}

// Показать сегодняшний APOD
function showTodayApod() {
    loadTodayApod();
}

// Переключение избранного для текущего APOD
function toggleFavorite() {
    if (!currentApod || !currentApod.date) return;
    
    const date = currentApod.date;
    favorites[date] = !favorites[date];
    
    // Сохраняем в localStorage
    localStorage.setItem('apod_favorites', JSON.stringify(favorites));
    
    // Обновляем кнопку
    updateFavoriteButton();
    
    // Обновляем счетчик
    updateFavoritesCount();
    
    // Показываем уведомление
    const message = favorites[date] ? 
        'Добавлено в избранное' : 'Удалено из избранного';
    showNotification(message, favorites[date] ? 'success' : 'info');
}

// Переключение избранного для архива
function toggleArchiveFavorite(date, button) {
    favorites[date] = !favorites[date];
    
    // Сохраняем в localStorage
    localStorage.setItem('apod_favorites', JSON.stringify(favorites));
    
    // Обновляем кнопку
    const icon = button.querySelector('i');
    if (favorites[date]) {
        button.className = 'btn btn-sm btn-warning';
        icon.className = 'fas fa-star';
    } else {
        button.className = 'btn btn-sm btn-outline-warning';
        icon.className = 'far fa-star';
    }
    
    // Обновляем счетчик
    updateFavoritesCount();
}

// Обновление кнопки избранного
function updateFavoriteButton() {
    const button = document.getElementById('favoriteBtn');
    const icon = button.querySelector('i');
    
    if (!currentApod || !currentApod.date) return;
    
    const isFavorite = favorites[currentApod.date] || false;
    
    if (isFavorite) {
        button.className = 'favorite-btn active';
        icon.className = 'fas fa-star';
    } else {
        button.className = 'favorite-btn';
        icon.className = 'far fa-star';
    }
}

// Обновление счетчика избранного
function updateFavoritesCount() {
    const favoriteCount = Object.values(favorites).filter(v => v).length;
    document.getElementById('favoritesCount').textContent = favoriteCount;
}

// Открытие лайтбокса
function openLightbox(url, title, date) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const lightboxDate = document.getElementById('lightboxDate');
    
    lightboxImage.src = url;
    lightboxTitle.textContent = title;
    lightboxDate.textContent = date;
    
    // Находим индекс текущего изображения
    lightboxIndex = lightboxImages.findIndex(img => img.url === url);
    
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Закрытие лайтбокса
function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    lightbox.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Следующее изображение в лайтбоксе
function nextLightbox() {
    if (lightboxImages.length === 0) return;
    
    lightboxIndex = (lightboxIndex + 1) % lightboxImages.length;
    const nextImage = lightboxImages[lightboxIndex];
    
    document.getElementById('lightboxImage').src = nextImage.url;
    document.getElementById('lightboxTitle').textContent = nextImage.title;
    document.getElementById('lightboxDate').textContent = nextImage.date;
}

// Предыдущее изображение в лайтбоксе
function prevLightbox() {
    if (lightboxImages.length === 0) return;
    
    lightboxIndex = (lightboxIndex - 1 + lightboxImages.length) % lightboxImages.length;
    const prevImage = lightboxImages[lightboxIndex];
    
    document.getElementById('lightboxImage').src = prevImage.url;
    document.getElementById('lightboxTitle').textContent = prevImage.title;
    document.getElementById('lightboxDate').textContent = prevImage.date;
}

// Просмотр деталей APOD из архива
function viewApodDetails(date) {
    fetch(`/api/apod?date=${date}`)
        .then(response => response.json())
        .then(data => {
            displayApodInModal(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки деталей:', error);
            showNotification('Ошибка загрузки деталей', 'danger');
        });
}

// Отображение APOD в модальном окне
function displayApodInModal(apod) {
    const modalContent = document.getElementById('apodInfoContent');
    const isImage = apod.media_type === 'image';
    
    modalContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                ${isImage ? `
                    <img src="${apod.url}" 
                         class="img-fluid rounded" 
                         alt="${apod.title}"
                         onclick="openLightbox('${apod.url}', '${apod.title}', '${apod.date}')">
                ` : `
                    <div class="video-container">
                        <iframe src="${apod.url}" 
                                allowfullscreen 
                                frameborder="0">
                        </iframe>
                    </div>
                `}
            </div>
            <div class="col-md-6">
                <h4>${apod.title}</h4>
                <div class="mb-3">
                    <small class="text-muted d-block">Дата</small>
                    <strong>${apod.date}</strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Автор</small>
                    <strong>${apod.copyright || 'NASA'}</strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Тип</small>
                    <span class="badge bg-${isImage ? 'info' : 'warning'}">
                        ${apod.media_type}
                    </span>
                </div>
                <div class="mt-4">
                    <h6>Описание</h6>
                    <p class="small">${apod.explanation || 'Описание отсутствует'}</p>
                </div>
                ${apod.hdurl ? `
                    <div class="mt-3">
                        <a href="${apod.hdurl}" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-info">
                            <i class="fas fa-external-link-alt me-1"></i> Открыть HD версию
                        </a>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('apodInfoModal'));
    modal.show();
}

// Скачать APOD
function downloadApod() {
    if (!currentApod || !currentApod.url) {
        showNotification('Нет данных для скачивания', 'warning');
        return;
    }
    
    const link = document.createElement('a');
    link.href = currentApod.url;
    link.download = `apod_${currentApod.date || 'today'}.${currentApod.url.split('.').pop()}`;
    link.click();
    
    showNotification('Начато скачивание', 'success');
}

// Экспорт архива
function exportArchive() {
    if (filteredArchive.length === 0) {
        showNotification('Нет данных для экспорта', 'warning');
        return;
    }
    
    // Создаем CSV
    let csv = 'Дата,Название,Тип,Автор,URL\n';
    
    filteredArchive.forEach(item => {
        csv += `"${item.date || ''}","${item.title || ''}","${item.media_type || ''}",`;
        csv += `"${item.copyright || 'NASA'}","${item.url || ''}"\n`;
    });
    
    // Создаем Blob и скачиваем
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `apod_archive_${new Date().toISOString().slice(0, 10)}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Архив экспортирован в CSV', 'success');
}

// Обновление статистики
function updateStats() {
    const images = archiveData.filter(item => item.media_type === 'image').length;
    const videos = archiveData.filter(item => item.media_type === 'video').length;
    const days = new Set(archiveData.map(item => item.date)).size;
    
    document.getElementById('totalImages').textContent = images;
    document.getElementById('totalVideos').textContent = videos;
    document.getElementById('daysCovered').textContent = days;
}

// Обновление статистики архива
function updateArchiveStats() {
    const images = filteredArchive.filter(item => item.media_type === 'image').length;
    const videos = filteredArchive.filter(item => item.media_type === 'video').length;
    
    document.getElementById('totalImages').textContent = images;
    document.getElementById('totalVideos').textContent = videos;
    document.getElementById('daysCovered').textContent = new Set(filteredArchive.map(item => item.date)).size;
}

// Открыть в новой вкладке
function openInNewTab() {
    if (currentApod && currentApod.url) {
        window.open(currentApod.url, '_blank');
    }
}

// Настройка обработчиков событий
function setupEventListeners() {
    // Фильтры архива
    const archiveForm = document.getElementById('archiveFilters');
    if (archiveForm) {
        archiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyArchiveFilters();
        });
    }
    
    // Элементы управления
    document.getElementById('archivePerPage').addEventListener('change', function() {
        archivePerPage = parseInt(this.value);
        currentArchivePage = 1;
        updateArchiveGrid();
    });
    
    // Закрытие лайтбокса по клику на фон
    document.getElementById('lightbox').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLightbox();
        }
    });
    
    // Закрытие лайтбокса по клавише ESC
    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('lightbox');
        if (e.key === 'Escape' && lightbox.style.display === 'flex') {
            closeLightbox();
        }
        
        // Навигация по лайтбоксу стрелками
        if (lightbox.style.display === 'flex') {
            if (e.key === 'ArrowRight') nextLightbox();
            if (e.key === 'ArrowLeft') prevLightbox();
        }
    });
}

// Показать уведомление
function showNotification(message, type = 'info') {
    // Удаляем старые уведомления
    const oldNotifications = document.querySelectorAll('.apod-notification');
    oldNotifications.forEach(n => n.remove());
    
    // Создаем новое уведомление
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} apod-notification position-fixed`;
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