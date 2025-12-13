<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\ApodController;
use App\Http\Controllers\NeoController;
use App\Http\Controllers\SpacexController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\SearchController;

// Главная страница → дашборд
Route::get('/', fn() => redirect('/dashboard'));

// Основные страницы (по ТЗ)
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/iss',      [IssController::class, 'index']);
Route::get('/apod',     [ApodController::class, 'index']);
Route::get('/neo',      [NeoController::class, 'index']);
Route::get('/spacex',   [SpacexController::class, 'index']);
Route::get('/osdr',     [OsdrController::class, 'index']);
Route::get('/search',   [SearchController::class, 'index'])->name('search');

// API маршруты (прокси к Rust)
Route::prefix('api/rust')->group(function () {
    Route::get('/iss/current', [IssController::class, 'apiCurrent']);
    Route::get('/iss/trend',   [IssController::class, 'apiTrend']);
    Route::get('/nasa/apod',   [ApodController::class, 'apiData']);
    Route::get('/nasa/neo',    [NeoController::class, 'apiData']);
    Route::get('/spacex/next', [SpacexController::class, 'apiNext']);
    Route::get('/osdr/list',   [OsdrController::class, 'apiList']);
});

// API поиска
Route::get('/api/search', [SearchController::class, 'apiSearch']);

// Для совместимости (можно временно оставить)
Route::get('/api/iss/last', fn() => redirect('/api/rust/iss/current'));
Route::get('/api/iss/trend', fn() => redirect('/api/rust/iss/trend'));

// Удалённые маршруты (комментируем):
// Route::get('/api/jwst/feed', ...);        // Удалено - не в ТЗ
// Route::get('/api/astro/events', ...);     // Удалено - не в ТЗ  
// Route::get('/page/{slug}', ...);          // Удалено - CMS не в ТЗ