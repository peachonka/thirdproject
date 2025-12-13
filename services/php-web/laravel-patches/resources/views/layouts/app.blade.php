<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Space Dashboard - @yield('title', 'Космические данные')</title>
  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Leaflet для карт -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  
  <!-- Font Awesome для иконок -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Наши стили -->
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  
  @stack('styles')
  
  <style>
    :root {
      --space-blue: #0d1b2a;
      --space-purple: #6c63ff;
      --space-teal: #00b4d8;
      --space-dark: #1a1a2e;
    }
    
    body {
      background: linear-gradient(135deg, #0d1b2a 0%, #1a1a2e 100%);
      color: #e0e1dd;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .navbar {
      background: rgba(26, 26, 46, 0.95) !important;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(108, 99, 255, 0.3);
    }
    
    .navbar-brand {
      color: #6c63ff !important;
      font-weight: bold;
      font-size: 1.5rem;
    }
    
    .nav-link {
      color: #e0e1dd !important;
      transition: color 0.3s ease;
    }
    
    .nav-link:hover {
      color: #6c63ff !important;
    }
    
    .card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(108, 99, 255, 0.2);
      border-radius: 15px;
      transition: all 0.3s ease;
    }
    
    .card:hover {
      transform: translateY(-5px);
      border-color: #6c63ff;
      box-shadow: 0 10px 25px rgba(108, 99, 255, 0.2);
    }
    
    .card-title {
      color: #6c63ff;
      border-bottom: 2px solid rgba(108, 99, 255, 0.3);
      padding-bottom: 10px;
    }
    
    .table {
      color: #e0e1dd;
    }
    
    .table th {
      border-color: rgba(108, 99, 255, 0.3);
      color: #6c63ff;
    }
    
    .table td {
      border-color: rgba(255, 255, 255, 0.1);
    }
    
    .form-control {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(108, 99, 255, 0.3);
      color: #e0e1dd;
    }
    
    .form-control:focus {
      background: rgba(255, 255, 255, 0.15);
      border-color: #6c63ff;
      box-shadow: 0 0 0 0.25rem rgba(108, 99, 255, 0.25);
      color: #e0e1dd;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #6c63ff 0%, #00b4d8 100%);
      border: none;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(108, 99, 255, 0.4);
    }
    
    /* Анимации */
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    
    .floating {
      animation: float 3s ease-in-out infinite;
    }
    
    /* Поисковая строка */
    .search-box {
      position: relative;
    }
    
    .search-box input {
      padding-right: 40px;
    }
    
    .search-box i {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c63ff;
    }
  </style>
</head>
<body>
<!-- Навбар с поиском -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container">
    <a class="navbar-brand floating" href="/dashboard">
      <i class="fas fa-rocket me-2"></i>Space Dashboard
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="/dashboard">
            <i class="fas fa-tachometer-alt me-1"></i>Дашборд
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/iss">
            <i class="fas fa-satellite me-1"></i>МКС
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/osdr">
            <i class="fas fa-database me-1"></i>OSDR
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/neo">
            <i class="fas fa-meteor me-1"></i>Астероиды
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/apod">
            <i class="fas fa-image me-1"></i>APOD
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/spacex">
            <i class="fas fa-space-shuttle me-1"></i>SpaceX
          </a>
        </li>
      </ul>
      
      <!-- Поиск по всему сайту -->
      <form class="d-flex search-box" id="globalSearchForm">
        <input class="form-control me-2" type="search" 
               placeholder="Поиск по данным..." 
               id="globalSearchInput">
        <i class="fas fa-search"></i>
      </form>
    </div>
  </div>
</nav>

<!-- Основной контент -->
<main class="container">
  @yield('content')
</main>

<!-- Футер -->
<footer class="mt-5 py-4 border-top border-space">
  <div class="container text-center text-muted">
    <p class="mb-0">
      <i class="fas fa-satellite me-1"></i>
      Космические данные в реальном времени • 
      <small>Обновлено: {{ now()->format('d.m.Y H:i') }}</small>
    </p>
  </div>
</footer>

<!-- Скрипты -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.plot.ly/plotly-2.24.1.min.js"></script>

<!-- Наши скрипты -->
<script src="{{ asset('js/animations.js') }}"></script>
<script src="{{ asset('js/filters.js') }}"></script>
<script src="{{ asset('js/search.js') }}"></script>

@stack('scripts')

<script>
// Инициализация глобального поиска
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('globalSearchInput');
  const searchForm = document.getElementById('globalSearchForm');
  
  if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const query = searchInput.value.trim();
      if (query) {
        window.location.href = `/search?q=${encodeURIComponent(query)}`;
      }
    });
  }
  
  // Автодополнение (простая версия)
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value;
      if (query.length > 2) {
        // Здесь можно добавить AJAX запрос для автодополнения
        console.log('Search suggestion for:', query);
      }
    });
  }
});
</script>
</body>
</html>