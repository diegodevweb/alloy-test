<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaskCacheService
{
    protected $defaultTtl = 3600;

    public function remember(string $key, callable $callback, array $tags = [], int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            } else {
                return Cache::remember($key, $ttl, $callback);
            }
        } catch (\Exception $e) {
            Log::warning("Erro no cache para chave {$key}: " . $e->getMessage());
            return $callback();
        }
    }

    public function put(string $key, mixed $value, array $tags = [], int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                return Cache::tags($tags)->put($key, $value, $ttl);
            } else {
                return Cache::put($key, $value, $ttl);
            }
        } catch (\Exception $e) {
            Log::warning("Erro ao armazenar no cache chave {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::warning("Erro ao remover do cache chave {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function invalidateTag(string $tag): bool
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags([$tag])->flush();
                return true;
            } else {
                $this->clearTasksCache();
                return true;
            }
        } catch (\Exception $e) {
            Log::warning("Erro ao invalidar tag {$tag}: " . $e->getMessage());
            return false;
        }
    }

    public function invalidateTags(array $tags): bool
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags($tags)->flush();
                return true;
            } else {
                $this->clearTasksCache();
                return true;
            }
        } catch (\Exception $e) {
            Log::warning("Erro ao invalidar tags: " . implode(', ', $tags) . ". Erro: " . $e->getMessage());
            return false;
        }
    }

    protected function clearTasksCache(): void
    {
        $patterns = [
            'tasks.index.*',
            'tasks.show.*',
        ];

        foreach ($patterns as $pattern) {
            if (method_exists(Cache::getStore(), 'flush')) {
                Cache::flush();
                break;
            }
        }
    }

    public function clearAll(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error("Erro ao limpar todo o cache: " . $e->getMessage());
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            Log::warning("Erro ao verificar existência da chave {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function generateKey(string $prefix, ...$params): string
    {
        $key = $prefix;
        foreach ($params as $param) {
            $key .= '.' . (is_array($param) ? md5(serialize($param)) : $param);
        }
        return $key;
    }
}
