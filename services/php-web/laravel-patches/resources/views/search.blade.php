@extends('layouts.app')

@section('title', 'Поиск по космическим данным')

@section('content')
<div class="row fade-in">
    <div class="col-12">
        <div class="card card-hover">
            <div class="card-body">
                <h3 class="card-title">
                    <i class="fas fa-search me-2"></i>Поиск по космическим данным
                </h3>
                
                <!-- Форма поиска -->
                <form action="{{ route('search') }}" method="GET" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               name="q" 
                               class="form-control" 
                               placeholder="Введите поисковый запрос..." 
                               value="{{ $query ?? '' }}"
                               autofocus>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Поиск
                        </button>
                    </div>
                    
                    <!-- Фильтры типа поиска -->
                    <div class="mt-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="typeAll" value="all" checked>
                            <label class="form-check-label" for="typeAll">Всё</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="typeIss" value="iss">
                            <label class="form-check-label" for="typeIss">МКС</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="typeOsdr" value="osdr">
                            <label class="form-check-label" for="typeOsdr">OSDR</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="typeAstro" value="astro">
                            <label class="form-check-label" for="typeAstro">Астрономия</label>
                        </div>
                    </div>
                </form>
                
                <!-- Результаты поиска -->
                @if(isset($query) && !empty($query))
                    <div class="search-results">
                        <h4 class="mb-4">
                            Результаты поиска для: <strong>"{{ $query }}"</strong>
                        </h4>
                        
                        @if(empty($results['iss']) && empty($results['osdr']) && empty($results['astro']))
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                По вашему запросу ничего не найдено.
                            </div>
                        @else
                            <!-- Результаты ISS -->
                            @if(!empty($results['iss']))
                                <div class="mb-4">
                                    <h5 class="text-space-teal">
                                        <i class="fas fa-satellite me-2"></i>Данные МКС
                                        <span class="badge bg-teal">{{ count($results['iss']) }}</span>
                                    </h5>
                                    <div class="row">
                                        @foreach($results['iss'] as $result)
                                            <div class="col-md-6 mb-3">
                                                <div class="card bg-dark border-space">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2 text-muted">
                                                            {{ $result['type'] }}
                                                        </h6>
                                                        <p class="card-text">{{ $result['value'] }}</p>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            {{ $result['timestamp'] ?? 'Неизвестно' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Результаты OSDR -->
                            @if(!empty($results['osdr']))
                                <div class="mb-4">
                                    <h5 class="text-space-purple">
                                        <i class="fas fa-database me-2"></i>Данные OSDR
                                        <span class="badge bg-purple">{{ count($results['osdr']) }}</span>
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-dark table-hover table-row-animate">
                                            <thead>
                                                <tr>
                                                    <th>Название</th>
                                                    <th>ID</th>
                                                    <th>Статус</th>
                                                    <th>Обновлено</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($results['osdr'] as $result)
                                                    <tr>
                                                        <td>{{ $result['title'] }}</td>
                                                        <td><code>{{ $result['id'] }}</code></td>
                                                        <td>
                                                            <span class="badge bg-{{ $result['status'] == 'completed' ? 'success' : 'warning' }}">
                                                                {{ $result['status'] }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $result['updated_at'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Результаты астрономии -->
                            @if(!empty($results['astro']))
                                <div class="mb-4">
                                    <h5 class="text-space-blue">
                                        <i class="fas fa-star me-2"></i>Астрономические события
                                        <span class="badge bg-blue">{{ count($results['astro']) }}</span>
                                    </h5>
                                    <div class="row">
                                        @foreach($results['astro'] as $result)
                                            <div class="col-md-4 mb-3">
                                                <div class="card bg-dark border-space">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2 text-muted">
                                                            {{ $result['type'] }}
                                                        </h6>
                                                        <p class="card-text">
                                                            <small>{{ $result['path'] }}</small><br>
                                                            {{ Str::limit($result['value'], 100) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Введите поисковый запрос</h4>
                        <p class="text-muted">Найдите данные по МКС, OSDR или астрономическим событиям</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Статистика поиска -->
@if(isset($query) && !empty($query))
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card card-hover">
            <div class="card-body text-center">
                <h3 class="text-space-teal">{{ count($results['iss'] ?? []) }}</h3>
                <p class="text-muted mb-0">Найдено в МКС</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-hover">
            <div class="card-body text-center">
                <h3 class="text-space-purple">{{ count($results['osdr'] ?? []) }}</h3>
                <p class="text-muted mb-0">Найдено в OSDR</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-hover">
            <div class="card-body text-center">
                <h3 class="text-space-blue">{{ count($results['astro'] ?? []) }}</h3>
                <p class="text-muted mb-0">Найдено в астрономии</p>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/search.js') }}"></script>
@endpush