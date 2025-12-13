<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SpacexController extends Controller
{
    private function base(): string
    {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function index(Request $request)
    {
        // Параметры фильтрации
        $status = $request->query('status', 'all');
        $year = $request->query('year', 'all');
        $rocket = $request->query('rocket', 'all');
        $site = $request->query('site', 'all');
        $sort_by = $request->query('sort_by', 'date_utc');
        $sort_dir = $request->query('sort_dir', 'desc');
        
        // Получаем данные
        $nextLaunch = $this->getNextLaunch();
        $launches = $this->getLaunches();
        $rockets = $this->getRockets();
        $launchpads = $this->getLaunchpads();
        
        // Фильтрация запусков
        $filteredLaunches = $this->filterLaunches($launches, [
            'status' => $status,
            'year' => $year,
            'rocket' => $rocket,
            'site' => $site,
        ]);
        
        // Сортировка
        $filteredLaunches = $this->sortLaunches($filteredLaunches, $sort_by, $sort_dir);
        
        // Статистика
        $stats = $this->calculateSpaceXStats($launches);

        return view('spacex', [
            'nextLaunch' => $nextLaunch,
            'launches' => array_slice($filteredLaunches, 0, 50), // Ограничиваем для отображения
            'rockets' => $rockets,
            'launchpads' => $launchpads,
            'filters' => [
                'status' => $status,
                'year' => $year,
                'rocket' => $rocket,
                'site' => $site,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
            ],
            'stats' => $stats,
            'years' => $this->extractYears($launches),
        ]);
    }

    public function launch(string $id)
    {
        $launch = $this->getLaunchById($id);
        
        if (!$launch) {
            abort(404, 'Запуск не найден');
        }

        $rocket = $this->getRocketById($launch['rocket'] ?? '');
        $launchpad = $this->getLaunchpadById($launch['launchpad'] ?? '');
        
        // Получаем похожие запуски
        $similarLaunches = $this->getSimilarLaunches($launch);

        return view('spacex-launch', [
            'launch' => $launch,
            'rocket' => $rocket,
            'launchpad' => $launchpad,
            'similarLaunches' => $similarLaunches,
            'crew' => $launch['crew'] ?? [],
            'payloads' => $launch['payloads'] ?? [],
            'links' => $launch['links'] ?? [],
        ]);
    }

    public function rockets()
    {
        $rockets = $this->getRockets();
        $stats = $this->calculateRocketStats($rockets);

        return view('spacex-rockets', [
            'rockets' => $rockets,
            'stats' => $stats,
        ]);
    }

    public function rocketDetails(string $id)
    {
        $rocket = $this->getRocketById($id);
        
        if (!$rocket) {
            abort(404, 'Ракета не найдена');
        }

        $launches = $this->getLaunchesByRocket($id);

        return view('spacex-rocket-details', [
            'rocket' => $rocket,
            'launches' => $launches,
            'success_rate' => $this->calculateRocketSuccessRate($id, $launches),
            'first_launch' => $this->getFirstLaunchDate($launches),
            'last_launch' => $this->getLastLaunchDate($launches),
        ]);
    }

    public function filter(Request $request)
    {
        $filters = $request->all();
        
        $launches = $this->getLaunches();
        $filteredLaunches = $this->filterLaunches($launches, $filters);
        
        // Сортировка
        $sort_by = $filters['sort_by'] ?? 'date_utc';
        $sort_dir = $filters['sort_dir'] ?? 'desc';
        $filteredLaunches = $this->sortLaunches($filteredLaunches, $sort_by, $sort_dir);

        return response()->json([
            'success' => true,
            'count' => count($filteredLaunches),
            'launches' => array_slice($filteredLaunches, 0, 100),
            'filters' => $filters,
        ]);
    }

    public function timeline()
    {
        $launches = $this->getLaunches();
        $timeline = $this->buildTimeline($launches);

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }

    private function getNextLaunch(): array
    {
        $cacheKey = 'spacex_next_launch';
        
        return Cache::remember($cacheKey, 300, function () {
            try {
                $base = $this->base();
                $response = Http::timeout(10)->get("{$base}/space/spacex/latest");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['payload'] ?? [];
                }
            } catch (\Exception $e) {
                \Log::error('SpaceX next launch fetch error: ' . $e->getMessage());
            }

            return [];
        });
    }

    private function getLaunches(): array
    {
        $cacheKey = 'spacex_launches';
        
        return Cache::remember($cacheKey, 3600, function () {
            try {
                $response = Http::timeout(30)->get('https://api.spacexdata.com/v4/launches');
                
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::error('SpaceX launches fetch error: ' . $e->getMessage());
            }

            return $this->getMockLaunches();
        });
    }

    private function getRockets(): array
    {
        $cacheKey = 'spacex_rockets';
        
        return Cache::remember($cacheKey, 86400, function () {
            try {
                $response = Http::timeout(15)->get('https://api.spacexdata.com/v4/rockets');
                
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::error('SpaceX rockets fetch error: ' . $e->getMessage());
            }

            return $this->getMockRockets();
        });
    }

    private function getLaunchpads(): array
    {
        $cacheKey = 'spacex_launchpads';
        
        return Cache::remember($cacheKey, 86400, function () {
            try {
                $response = Http::timeout(15)->get('https://api.spacexdata.com/v4/launchpads');
                
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::error('SpaceX launchpads fetch error: ' . $e->getMessage());
            }

            return $this->getMockLaunchpads();
        });
    }

    private function getLaunchById(string $id): ?array
    {
        $launches = $this->getLaunches();
        
        foreach ($launches as $launch) {
            if ($launch['id'] === $id) {
                return $launch;
            }
        }
        
        return null;
    }

    private function getRocketById(string $id): ?array
    {
        $rockets = $this->getRockets();
        
        foreach ($rockets as $rocket) {
            if ($rocket['id'] === $id) {
                return $rocket;
            }
        }
        
        return null;
    }

    private function getLaunchpadById(string $id): ?array
    {
        $launchpads = $this->getLaunchpads();
        
        foreach ($launchpads as $launchpad) {
            if ($launchpad['id'] === $id) {
                return $launchpad;
            }
        }
        
        return null;
    }

    private function filterLaunches(array $launches, array $filters): array
    {
        return array_filter($launches, function ($launch) use ($filters) {
            // Фильтр по статусу
            if ($filters['status'] !== 'all') {
                $status = $launch['success'] ?? null;
                $upcoming = $launch['upcoming'] ?? false;
                
                if ($filters['status'] === 'success' && (!$status || $upcoming)) {
                    return false;
                }
                if ($filters['status'] === 'failed' && ($status || $upcoming)) {
                    return false;
                }
                if ($filters['status'] === 'upcoming' && !$upcoming) {
                    return false;
                }
            }
            
            // Фильтр по году
            if ($filters['year'] !== 'all' && isset($launch['date_utc'])) {
                $year = date('Y', strtotime($launch['date_utc']));
                if ($year !== $filters['year']) {
                    return false;
                }
            }
            
            // Фильтр по ракете
            if ($filters['rocket'] !== 'all' && isset($launch['rocket'])) {
                if ($launch['rocket'] !== $filters['rocket']) {
                    return false;
                }
            }
            
            // Фильтр по площадке
            if ($filters['site'] !== 'all' && isset($launch['launchpad'])) {
                if ($launch['launchpad'] !== $filters['site']) {
                    return false;
                }
            }
            
            return true;
        });
    }

    private function sortLaunches(array $launches, string $sort_by, string $sort_dir): array
    {
        usort($launches, function($a, $b) use ($sort_by, $sort_dir) {
            $aVal = $a[$sort_by] ?? '';
            $bVal = $b[$sort_by] ?? '';
            
            // Для дат преобразуем в timestamp
            if (strpos($sort_by, 'date') !== false) {
                $aVal = strtotime($aVal);
                $bVal = strtotime($bVal);
            }
            
            if ($sort_dir === 'desc') {
                return $bVal <=> $aVal;
            }
            return $aVal <=> $bVal;
        });
        
        return $launches;
    }

    private function calculateSpaceXStats(array $launches): array
    {
        $stats = [
            'total' => count($launches),
            'successful' => 0,
            'failed' => 0,
            'upcoming' => 0,
            'rockets' => [],
            'years' => [],
            'total_crew' => 0,
            'total_payloads' => 0,
        ];
        
        foreach ($launches as $launch) {
            if ($launch['upcoming'] ?? false) {
                $stats['upcoming']++;
            } elseif ($launch['success'] ?? false) {
                $stats['successful']++;
            } else {
                $stats['failed']++;
            }
            
            // Ракеты
            $rocket = $launch['rocket'] ?? 'unknown';
            if (!isset($stats['rockets'][$rocket])) {
                $stats['rockets'][$rocket] = 0;
            }
            $stats['rockets'][$rocket]++;
            
            // Годы
            if (isset($launch['date_utc'])) {
                $year = date('Y', strtotime($launch['date_utc']));
                if (!in_array($year, $stats['years'])) {
                    $stats['years'][] = $year;
                }
            }
            
            // Экипаж и полезная нагрузка
            $stats['total_crew'] += count($launch['crew'] ?? []);
            $stats['total_payloads'] += count($launch['payloads'] ?? []);
        }
        
        sort($stats['years']);
        arsort($stats['rockets']);
        
        $stats['success_rate'] = $stats['total'] > 0 ? 
            round(($stats['successful'] / ($stats['total'] - $stats['upcoming'])) * 100, 2) : 0;
        
        return $stats;
    }

    private function extractYears(array $launches): array
    {
        $years = [];
        
        foreach ($launches as $launch) {
            if (isset($launch['date_utc'])) {
                $year = date('Y', strtotime($launch['date_utc']));
                if (!in_array($year, $years)) {
                    $years[] = $year;
                }
            }
        }
        
        sort($years);
        return $years;
    }

    private function getLaunchesByRocket(string $rocketId): array
    {
        $launches = $this->getLaunches();
        
        return array_filter($launches, function ($launch) use ($rocketId) {
            return ($launch['rocket'] ?? '') === $rocketId;
        });
    }

    private function getSimilarLaunches(array $currentLaunch): array
    {
        $launches = $this->getLaunches();
        $similar = [];
        
        $currentRocket = $currentLaunch['rocket'] ?? '';
        $currentPayloadCount = count($currentLaunch['payloads'] ?? []);
        
        foreach ($launches as $launch) {
            if ($launch['id'] === $currentLaunch['id']) {
                continue;
            }
            
            $score = 0;
            
            // Сравнение ракет
            if ($launch['rocket'] === $currentRocket) {
                $score += 3;
            }
            
            // Сравнение количества полезной нагрузки
            $payloadCount = count($launch['payloads'] ?? []);
            if (abs($payloadCount - $currentPayloadCount) <= 1) {
                $score += 2;
            }
            
            // Сравнение года запуска
            if (isset($launch['date_utc']) && isset($currentLaunch['date_utc'])) {
                $year1 = date('Y', strtotime($launch['date_utc']));
                $year2 = date('Y', strtotime($currentLaunch['date_utc']));
                if ($year1 === $year2) {
                    $score += 1;
                }
            }
            
            if ($score > 0) {
                $launch['_similarity_score'] = $score;
                $similar[] = $launch;
            }
        }
        
        // Сортировка по схожести
        usort($similar, function($a, $b) {
            return ($b['_similarity_score'] ?? 0) <=> ($a['_similarity_score'] ?? 0);
        });
        
        return array_slice($similar, 0, 5);
    }

    private function calculateRocketSuccessRate(string $rocketId, array $launches): float
    {
        $completed = 0;
        $successful = 0;
        
        foreach ($launches as $launch) {
            if (!($launch['upcoming'] ?? false)) {
                $completed++;
                if ($launch['success'] ?? false) {
                    $successful++;
                }
            }
        }
        
        return $completed > 0 ? round(($successful / $completed) * 100, 2) : 0;
    }

    private function getFirstLaunchDate(array $launches): ?string
    {
        $dates = [];
        
        foreach ($launches as $launch) {
            if (isset($launch['date_utc']) && !($launch['upcoming'] ?? false)) {
                $dates[] = strtotime($launch['date_utc']);
            }
        }
        
        return !empty($dates) ? date('Y-m-d', min($dates)) : null;
    }

    private function getLastLaunchDate(array $launches): ?string
    {
        $dates = [];
        
        foreach ($launches as $launch) {
            if (isset($launch['date_utc']) && !($launch['upcoming'] ?? false)) {
                $dates[] = strtotime($launch['date_utc']);
            }
        }
        
        return !empty($dates) ? date('Y-m-d', max($dates)) : null;
    }

    private function calculateRocketStats(array $rockets): array
    {
        $stats = [
            'total' => count($rockets),
            'active' => 0,
            'retired' => 0,
            'under_development' => 0,
            'total_launches' => 0,
            'success_rate' => 0,
            'cost_per_launch' => [],
        ];
        
        foreach ($rockets as $rocket) {
            if ($rocket['active'] ?? false) {
                $stats['active']++;
            } else {
                $stats['retired']++;
            }
            
            if ($rocket['under_development'] ?? false) {
                $stats['under_development']++;
            }
            
            $stats['total_launches'] += $rocket['launches'] ?? 0;
            $stats['success_rate'] += $rocket['success_rate_pct'] ?? 0;
            
            if (isset($rocket['cost_per_launch'])) {
                $stats['cost_per_launch'][] = $rocket['cost_per_launch'];
            }
        }
        
        $stats['success_rate'] = $stats['total'] > 0 ? 
            round($stats['success_rate'] / $stats['total'], 2) : 0;
        
        $stats['avg_cost_per_launch'] = !empty($stats['cost_per_launch']) ? 
            round(array_sum($stats['cost_per_launch']) / count($stats['cost_per_launch'])) : 0;
        
        return $stats;
    }

    private function buildTimeline(array $launches): array
    {
        $timeline = [];
        
        foreach ($launches as $launch) {
            if (!isset($launch['date_utc'])) {
                continue;
            }
            
            $year = date('Y', strtotime($launch['date_utc']));
            $month = date('m', strtotime($launch['date_utc']));
            
            if (!isset($timeline[$year])) {
                $timeline[$year] = [];
            }
            
            if (!isset($timeline[$year][$month])) {
                $timeline[$year][$month] = [
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0,
                    'upcoming' => 0,
                ];
            }
            
            $timeline[$year][$month]['total']++;
            
            if ($launch['upcoming'] ?? false) {
                $timeline[$year][$month]['upcoming']++;
            } elseif ($launch['success'] ?? false) {
                $timeline[$year][$month]['successful']++;
            } else {
                $timeline[$year][$month]['failed']++;
            }
        }
        
        // Сортируем годы по убыванию
        krsort($timeline);
        
        // В каждом году сортируем месяцы по убыванию
        foreach ($timeline as $year => &$months) {
            krsort($months);
        }
        
        return $timeline;
    }

    // Мок данные для демо
    private function getMockLaunches(): array
    {
        return [
            [
                'id' => 'mock1',
                'name' => 'Starlink Group 6-50',
                'date_utc' => date('Y-m-d', strtotime('+7 days')),
                'upcoming' => true,
                'success' => null,
                'rocket' => 'falcon9',
                'launchpad' => 'ksc_lc_39a',
                'crew' => [],
                'payloads' => ['mock_payload1'],
                'links' => [
                    'webcast' => 'https://www.youtube.com/watch?v=mock',
                    'article' => 'https://spacex.com/mock',
                ],
            ],
            // Добавь больше мок данных при необходимости
        ];
    }

    private function getMockRockets(): array
    {
        return [
            [
                'id' => 'falcon9',
                'name' => 'Falcon 9',
                'active' => true,
                'success_rate_pct' => 98,
                'launches' => 280,
                'cost_per_launch' => 50000000,
                'description' => 'Частично многоразовая ракета-носитель',
            ],
            // Добавь больше мок данных при необходимости
        ];
    }

    private function getMockLaunchpads(): array
    {
        return [
            [
                'id' => 'ksc_lc_39a',
                'name' => 'Kennedy Space Center Launch Complex 39A',
                'full_name' => 'NASA Kennedy Space Center Launch Complex 39A',
                'locality' => 'Cape Canaveral',
                'region' => 'Florida',
                'launch_attempts' => 150,
                'launch_successes' => 145,
            ],
           
        ];
    }
}