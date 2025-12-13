<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NeoController extends Controller
{
    private function base(): string
    {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function index(Request $request)
    {
        // Получаем данные из кэша Rust сервиса
        $neoData = $this->getNeoData();
        
        // Параметры фильтрации из запроса
        $filters = [
            'hazardous' => $request->query('hazardous', 'all'),
            'size_min' => $request->query('size_min', 0),
            'size_max' => $request->query('size_max', 1000),
            'date_from' => $request->query('date_from', now()->subDays(7)->format('Y-m-d')),
            'date_to' => $request->query('date_to', now()->format('Y-m-d')),
            'sort_by' => $request->query('sort_by', 'date'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        // Фильтрация данных
        $asteroids = $this->filterAsteroids($neoData, $filters);
        
        // Статистика
        $stats = $this->calculateStats($asteroids);

        return view('neo', [
            'asteroids' => $asteroids,
            'filters' => $filters,
            'stats' => $stats,
            'neoData' => $neoData,
            'lastUpdated' => $neoData['fetched_at'] ?? null,
        ]);
    }

    public function details(string $id)
    {
        $neoData = $this->getNeoData();
        $asteroid = null;

        // Ищем астероид по ID
        if (isset($neoData['near_earth_objects'])) {
            foreach ($neoData['near_earth_objects'] as $date => $asteroids) {
                foreach ($asteroids as $ast) {
                    if ($ast['id'] == $id) {
                        $asteroid = $ast;
                        $asteroid['date'] = $date;
                        break 2;
                    }
                }
            }
        }

        if (!$asteroid) {
            abort(404, 'Астероид не найден');
        }

        return view('neo-details', [
            'asteroid' => $asteroid,
            'closeApproaches' => $asteroid['close_approach_data'] ?? [],
        ]);
    }

    public function filter(Request $request)
    {
        $neoData = $this->getNeoData();
        $filters = $request->all();
        
        $asteroids = $this->filterAsteroids($neoData, $filters);
        $stats = $this->calculateStats($asteroids);

        return response()->json([
            'success' => true,
            'count' => count($asteroids),
            'asteroids' => array_slice($asteroids, 0, 50), // Ограничиваем для ответа
            'stats' => $stats,
        ]);
    }

    public function hazardous()
    {
        $neoData = $this->getNeoData();
        $hazardous = [];

        if (isset($neoData['near_earth_objects'])) {
            foreach ($neoData['near_earth_objects'] as $date => $asteroids) {
                foreach ($asteroids as $asteroid) {
                    if ($asteroid['is_potentially_hazardous_asteroid']) {
                        $hazardous[] = [
                            'date' => $date,
                            ...$asteroid
                        ];
                    }
                }
            }
        }

        return response()->json([
            'count' => count($hazardous),
            'hazardous' => $hazardous,
        ]);
    }

    private function getNeoData(): array
    {
        try {
            $base = $this->base();
            $response = Http::timeout(10)->get("{$base}/space/neo/latest");
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['payload'] ?? [];
            }
        } catch (\Exception $e) {
            \Log::error('NEO data fetch error: ' . $e->getMessage());
        }

        return [];
    }

    private function filterAsteroids(array $neoData, array $filters): array
    {
        $asteroids = [];

        if (empty($neoData) || !isset($neoData['near_earth_objects'])) {
            return [];
        }

        foreach ($neoData['near_earth_objects'] as $date => $dateAsteroids) {
            // Фильтр по дате
            if (isset($filters['date_from']) && $date < $filters['date_from']) {
                continue;
            }
            if (isset($filters['date_to']) && $date > $filters['date_to']) {
                continue;
            }

            foreach ($dateAsteroids as $asteroid) {
                // Фильтр по опасности
                if ($filters['hazardous'] === 'only' && !$asteroid['is_potentially_hazardous_asteroid']) {
                    continue;
                }
                if ($filters['hazardous'] === 'safe' && $asteroid['is_potentially_hazardous_asteroid']) {
                    continue;
                }

                // Фильтр по размеру
                $diameter = $asteroid['estimated_diameter']['kilometers']['estimated_diameter_max'] ?? 0;
                if ($diameter < $filters['size_min'] || $diameter > $filters['size_max']) {
                    continue;
                }

                // Добавляем дату для сортировки
                $asteroid['_date'] = $date;
                $asteroid['_diameter'] = $diameter;
                
                // Расчет скорости (берем первую близкую встречу)
                $velocity = 0;
                if (!empty($asteroid['close_approach_data'])) {
                    $approach = $asteroid['close_approach_data'][0];
                    $velocity = (float) ($approach['relative_velocity']['kilometers_per_hour'] ?? 0);
                }
                $asteroid['_velocity'] = $velocity;

                $asteroids[] = $asteroid;
            }
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'date';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        usort($asteroids, function($a, $b) use ($sortBy, $sortDir) {
            $aVal = $a['_' . $sortBy] ?? $a[$sortBy] ?? 0;
            $bVal = $b['_' . $sortBy] ?? $b[$sortBy] ?? 0;

            if ($sortDir === 'desc') {
                return $bVal <=> $aVal;
            }
            return $aVal <=> $bVal;
        });

        return $asteroids;
    }

    private function calculateStats(array $asteroids): array
    {
        $stats = [
            'total' => count($asteroids),
            'hazardous' => 0,
            'max_diameter' => 0,
            'min_diameter' => PHP_FLOAT_MAX,
            'avg_diameter' => 0,
            'total_velocity' => 0,
        ];

        $diameterSum = 0;
        $velocitySum = 0;

        foreach ($asteroids as $asteroid) {
            $diameter = $asteroid['_diameter'] ?? 0;
            
            if ($asteroid['is_potentially_hazardous_asteroid']) {
                $stats['hazardous']++;
            }

            $stats['max_diameter'] = max($stats['max_diameter'], $diameter);
            $stats['min_diameter'] = min($stats['min_diameter'], $diameter);
            $diameterSum += $diameter;
            $velocitySum += $asteroid['_velocity'] ?? 0;
        }

        if ($stats['total'] > 0) {
            $stats['avg_diameter'] = $diameterSum / $stats['total'];
            $stats['avg_velocity'] = $velocitySum / $stats['total'];
            $stats['hazardous_percentage'] = ($stats['hazardous'] / $stats['total']) * 100;
        } else {
            $stats['min_diameter'] = 0;
            $stats['avg_diameter'] = 0;
            $stats['avg_velocity'] = 0;
            $stats['hazardous_percentage'] = 0;
        }

        return $stats;
    }
}