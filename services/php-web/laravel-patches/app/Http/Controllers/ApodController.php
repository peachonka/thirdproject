<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ApodController extends Controller
{
    private function base(): string
    {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3001';
    }

    public function index(Request $request)
    {
        // Параметры фильтрации
        $date = $request->query('date', now()->format('Y-m-d'));
        $start_date = $request->query('start_date', now()->subDays(30)->format('Y-m-d'));
        $end_date = $request->query('end_date', now()->format('Y-m-d'));
        $count = $request->query('count', 10);
        $page = $request->query('page', 1);
        
        // Получаем данные
        $apodData = $this->getApodData($date);
        $archive = $this->getApodArchive($start_date, $end_date, $count, $page);

        return view('apod', [
            'apod' => $apodData,
            'archive' => $archive['items'] ?? [],
            'pagination' => $archive['pagination'] ?? null,
            'filters' => [
                'date' => $date,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'count' => $count,
                'page' => $page,
            ],
            'stats' => $this->calculateApodStats($archive['items'] ?? []),
        ]);
    }

    public function details(string $date)
    {
        $apodData = $this->getApodData($date);

        if (empty($apodData)) {
            abort(404, 'APOD не найден для указанной даты');
        }

        return view('apod-details', [
            'apod' => $apodData,
            'date' => $date,
        ]);
    }

    public function random()
    {
        // Генерируем случайную дату за последние 20 лет
        $randomDate = now()->subDays(rand(1, 365 * 20))->format('Y-m-d');
        
        $apodData = $this->getApodData($randomDate);

        if (empty($apodData)) {
            // Если нет данных для случайной даты, берем сегодняшние
            $apodData = $this->getApodData(now()->format('Y-m-d'));
        }

        return response()->json([
            'success' => true,
            'date' => $randomDate,
            'apod' => $apodData,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->query('q', '');
        $type = $request->query('type', 'all'); // image, video, all
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Поисковый запрос не может быть пустым',
            ], 400);
        }

        // Получаем архив за последний год для поиска
        $start_date = now()->subDays(365)->format('Y-m-d');
        $end_date = now()->format('Y-m-d');
        
        $archive = $this->getApodArchive($start_date, $end_date, 100, 1);
        $items = $archive['items'] ?? [];

        // Поиск по заголовку и описанию
        $results = [];
        foreach ($items as $item) {
            $matches = false;
            
            // Поиск в заголовке
            if (stripos($item['title'] ?? '', $query) !== false) {
                $matches = true;
            }
            
            // Поиск в описании
            if (stripos($item['explanation'] ?? '', $query) !== false) {
                $matches = true;
            }
            
            // Фильтр по типу
            if ($type !== 'all') {
                $itemType = strtolower($item['media_type'] ?? 'image');
                if ($type === 'image' && $itemType !== 'image') {
                    $matches = false;
                } elseif ($type === 'video' && $itemType !== 'video') {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $results[] = $item;
            }
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'count' => count($results),
            'results' => $results,
        ]);
    }

    public function filter(Request $request)
    {
        $filters = $request->all();
        
        $start_date = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $end_date = $filters['end_date'] ?? now()->format('Y-m-d');
        $count = $filters['count'] ?? 20;
        $page = $filters['page'] ?? 1;
        $type = $filters['type'] ?? 'all';
        $sort_by = $filters['sort_by'] ?? 'date';
        $sort_dir = $filters['sort_dir'] ?? 'desc';

        $archive = $this->getApodArchive($start_date, $end_date, 1000, 1); // Большой лимит для фильтрации
        $items = $archive['items'] ?? [];

        // Применяем фильтры
        $filteredItems = array_filter($items, function($item) use ($type) {
            if ($type !== 'all') {
                $itemType = strtolower($item['media_type'] ?? 'image');
                return $itemType === $type;
            }
            return true;
        });

        // Сортировка
        usort($filteredItems, function($a, $b) use ($sort_by, $sort_dir) {
            $aVal = $a[$sort_by] ?? '';
            $bVal = $b[$sort_by] ?? '';

            if ($sort_by === 'date') {
                $aVal = strtotime($aVal);
                $bVal = strtotime($bVal);
            }

            if ($sort_dir === 'desc') {
                return $bVal <=> $aVal;
            }
            return $aVal <=> $bVal;
        });

        // Пагинация
        $perPage = $count;
        $total = count($filteredItems);
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($filteredItems, $offset, $perPage);

        return response()->json([
            'success' => true,
            'items' => $paginatedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $pages,
            ],
            'filters' => $filters,
        ]);
    }

    private function getApodData(string $date = null): array
    {
        $cacheKey = 'apod_' . ($date ?? 'latest');
        
        return Cache::remember($cacheKey, 3600, function () use ($date) {
            try {
                $base = $this->base();
                $url = "{$base}/space/apod/latest";
                
                $response = Http::timeout(15)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Если запрашиваем конкретную дату, пытаемся найти в архиве
                    if ($date && isset($data['payload']['date'])) {
                        if ($data['payload']['date'] !== $date) {
                            // Не совпадает дата, возвращаем пустой массив
                            return [];
                        }
                    }
                    
                    return $data['payload'] ?? [];
                }
            } catch (\Exception $e) {
                \Log::error('APOD data fetch error: ' . $e->getMessage());
            }

            return [];
        });
    }

    private function getApodArchive(string $start_date, string $end_date, int $count = 10, int $page = 1): array
    {
        // Реализация получения архива APOD
        // В реальном проекте здесь был бы вызов NASA API
        // Для демо возвращаем мок данные
        
        $mockArchive = $this->generateMockArchive($start_date, $end_date);
        
        // Пагинация
        $perPage = $count;
        $total = count($mockArchive);
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($mockArchive, $offset, $perPage);

        return [
            'items' => $paginatedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $pages,
            ],
        ];
    }

    private function generateMockArchive(string $start_date, string $end_date): array
    {
        $archive = [];
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        
        $titles = [
            "Красивая туманность", "Галактика Андромеды", "Спиральная галактика", 
            "Звездное скопление", "Северное сияние", "Луна и Юпитер",
            "Марсианский пейзаж", "Солнечное затмение", "Комета NEOWISE",
            "Столпы творения", "Кратер на Марсе", "Спутник Сатурна"
        ];
        
        $descriptions = [
            "Невероятное изображение космического объекта",
            "Фотография сделана космическим телескопом Хаббл",
            "Уникальный снимок, показывающий красоту вселенной",
            "Это изображение демонстрирует процессы звездообразования",
            "Цвета на изображении соответствуют различным химическим элементам"
        ];
        
        $current = $end;
        $counter = 0;
        
        while ($current >= $start && $counter < 50) {
            $date = date('Y-m-d', $current);
            $title = $titles[array_rand($titles)];
            $description = $descriptions[array_rand($descriptions)];
            
            $archive[] = [
                'date' => $date,
                'title' => $title,
                'explanation' => $description . " (" . $date . ")",
                'url' => "https://apod.nasa.gov/apod/image/" . date('ymd', $current) . "_mock.jpg",
                'hdurl' => "https://apod.nasa.gov/apod/image/" . date('ymd', $current) . "_mock_hd.jpg",
                'media_type' => 'image',
                'copyright' => 'Mock Data for Demo',
                'service_version' => 'v1',
            ];
            
            $current -= 86400; // Минус один день
            $counter++;
        }
        
        return $archive;
    }

    private function calculateApodStats(array $items): array
    {
        $stats = [
            'total' => count($items),
            'images' => 0,
            'videos' => 0,
            'years_covered' => [],
            'copyright_counts' => [],
        ];

        foreach ($items as $item) {
            // Подсчет типов медиа
            $mediaType = strtolower($item['media_type'] ?? 'image');
            if ($mediaType === 'image') {
                $stats['images']++;
            } elseif ($mediaType === 'video') {
                $stats['videos']++;
            }

            // Годы
            if (isset($item['date'])) {
                $year = substr($item['date'], 0, 4);
                if (!in_array($year, $stats['years_covered'])) {
                    $stats['years_covered'][] = $year;
                }
            }

            // Авторы/копирайты
            $copyright = $item['copyright'] ?? 'Unknown';
            if (!isset($stats['copyright_counts'][$copyright])) {
                $stats['copyright_counts'][$copyright] = 0;
            }
            $stats['copyright_counts'][$copyright]++;
        }

        sort($stats['years_covered']);
        arsort($stats['copyright_counts']);
        $stats['top_copyright'] = array_slice($stats['copyright_counts'], 0, 5, true);

        return $stats;
    }
}