<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    private function base(): string
    {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $results = [];
        
        if (!empty($query)) {
            // Поиск по ISS данным
            $issResults = $this->searchIss($query);
            
            // Поиск по OSDR данным
            $osdrResults = $this->searchOsdr($query);
            
            // Поиск по астрономическим событиям
            $astroResults = $this->searchAstro($query);
            
            $results = [
                'iss' => $issResults,
                'osdr' => $osdrResults,
                'astro' => $astroResults,
                'query' => $query
            ];
        }
        
        return view('search', [
            'results' => $results,
            'query' => $query
        ]);
    }
    
    private function searchIss(string $query): array
    {
        try {
            $base = $this->base();
            $response = Http::timeout(10)->get("{$base}/last");
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Простой поиск по ключевым словам в данных ISS
                $matches = [];
                $jsonString = json_encode($data);
                
                if (stripos($jsonString, $query) !== false) {
                    $matches[] = [
                        'type' => 'ISS Position',
                        'value' => $data['payload']['latitude'] ?? 0 . ', ' . 
                                  $data['payload']['longitude'] ?? 0,
                        'timestamp' => $data['fetched_at'] ?? null,
                        'score' => 1
                    ];
                }
                
                return $matches;
            }
        } catch (\Exception $e) {
            // Логирование ошибки
            \Log::error('ISS search error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    private function searchOsdr(string $query): array
    {
        try {
            $base = $this->base();
            $response = Http::timeout(10)->get("{$base}/osdr/list");
            
            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];
                $matches = [];
                
                foreach ($items as $item) {
                    $title = $item['title'] ?? '';
                    $datasetId = $item['dataset_id'] ?? '';
                    $jsonString = json_encode($item);
                    
                    // Проверяем совпадение
                    if (stripos($title, $query) !== false || 
                        stripos($datasetId, $query) !== false ||
                        stripos($jsonString, $query) !== false) {
                        
                        $matches[] = [
                            'type' => 'OSDR Dataset',
                            'title' => $title,
                            'id' => $datasetId,
                            'status' => $item['status'] ?? null,
                            'updated_at' => $item['updated_at'] ?? null,
                            'score' => stripos($title, $query) !== false ? 2 : 1
                        ];
                    }
                }
                
                // Сортировка по релевантности
                usort($matches, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                return $matches;
            }
        } catch (\Exception $e) {
            \Log::error('OSDR search error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    private function searchAstro(string $query): array
    {
        try {
            $base = $this->base();
            $response = Http::timeout(10)->get("{$base}/space/astro/latest");
            
            if ($response->successful()) {
                $data = $response->json();
                $matches = [];
                
                // Рекурсивный поиск в JSON
                $this->searchAstroRecursive($data, $query, $matches);
                
                return $matches;
            }
        } catch (\Exception $e) {
            \Log::error('Astro search error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    private function searchAstroRecursive($data, $query, &$matches, $path = '')
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $currentPath = $path ? $path . '.' . $key : $key;
                
                if (is_string($value) && stripos($value, $query) !== false) {
                    $matches[] = [
                        'path' => $currentPath,
                        'value' => $value,
                        'type' => 'Astronomy Event'
                    ];
                }
                
                if (is_array($value) || is_object($value)) {
                    $this->searchAstroRecursive($value, $query, $matches, $currentPath);
                }
            }
        }
    }
    
    public function apiSearch(Request $request)
    {
        $query = $request->input('q', '');
        $type = $request->input('type', 'all');
        
        $results = [];
        
        switch ($type) {
            case 'iss':
                $results = $this->searchIss($query);
                break;
            case 'osdr':
                $results = $this->searchOsdr($query);
                break;
            case 'astro':
                $results = $this->searchAstro($query);
                break;
            default:
                $results = array_merge(
                    $this->searchIss($query),
                    $this->searchOsdr($query),
                    $this->searchAstro($query)
                );
        }
        
        return response()->json([
            'success' => true,
            'query' => $query,
            'type' => $type,
            'count' => count($results),
            'results' => $results
        ]);
    }
}