<?php

declare(strict_types=1);

namespace MrtgSensor\Cache;

use Phpfastcache\CacheManager;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * Cache adapter wrapping phpfastcache with a simplified interface
 *
 * Provides backward compatibility with the original FileCache API
 * while using the robust phpfastcache library underneath.
 */
final class CacheAdapter
{
    private ExtendedCacheItemPoolInterface $cache;

    private readonly bool $enabled;

    /**
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct(string $cacheDir = 'var/cache')
    {
        try {
            // Ensure cache directory exists
            if (! is_dir($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }

            // Create a unique instance ID to avoid conflicts between tests
            $instanceId = 'files_'.md5($cacheDir);

            // Configure Files driver with proper IO configuration
            $config = new FilesConfig;
            $config->setPath($cacheDir);
            $config->setDefaultTtl(300);
            $config->setPreventCacheSlams(true);
            // Keep static item caching enabled (required for save() method)
            $config->setUseStaticItemCaching(true);

            // Get Files driver instance
            $this->cache = CacheManager::getInstance('files', $config, $instanceId);

            $this->enabled = true;
        } catch (\Exception $e) {
            // If cache initialization fails, disable caching gracefully
            $this->enabled = false;
        }
    }

    /**
     * Get cached data by ID and group
     *
     * @param  string  $id  Cache identifier
     * @param  string  $group  Cache group/namespace
     * @param  int  $maxSeconds  Maximum age in seconds (TTL)
     * @return string|null Cached data or null if not found/expired
     */
    public function get(string $id, string $group = 'cache', int $maxSeconds = 295): ?string
    {
        if (! $this->enabled) {
            return null;
        }

        try {
            $cacheKey = $this->makeCacheKey($id, $group);
            $item = $this->cache->getItem($cacheKey);

            if (! $item->isHit()) {
                return null;
            }

            // phpfastcache handles TTL automatically via expiresAfter()
            // We just need to retrieve the value
            $value = $item->get();

            return is_string($value) ? $value : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get cached array data by ID and group
     *
     * @param  string  $id  Cache identifier
     * @param  string  $group  Cache group/namespace
     * @param  int  $maxSeconds  Maximum age in seconds (TTL)
     * @return array<mixed>|null Cached array or null if not found/expired
     */
    public function getArray(string $id, string $group = 'cache', int $maxSeconds = 295): ?array
    {
        $data = $this->get($id, $group, $maxSeconds);
        if ($data === null) {
            return null;
        }

        $result = unserialize($data);

        return is_array($result) ? $result : null;
    }

    /**
     * Store string data in cache
     *
     * @param  string  $id  Cache identifier
     * @param  string  $group  Cache group/namespace
     * @param  string  $value  Data to cache
     * @param  int  $ttl  Time-to-live in seconds (default: 295)
     * @return bool True on success, false on failure
     */
    public function set(string $id, string $group, string $value, int $ttl = 295): bool
    {
        if (! $this->enabled) {
            return false;
        }

        try {
            $cacheKey = $this->makeCacheKey($id, $group);
            $item = $this->cache->getItem($cacheKey);

            $item->set($value);
            $item->expiresAfter($ttl);

            return $this->cache->save($item);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Store array data in cache
     *
     * @param  string  $id  Cache identifier
     * @param  string  $group  Cache group/namespace
     * @param  array<mixed>  $array  Data to cache
     * @param  int  $ttl  Time-to-live in seconds (default: 295)
     * @return bool True on success, false on failure
     */
    public function setArray(string $id, string $group, array $array, int $ttl = 295): bool
    {
        return $this->set($id, $group, serialize($array), $ttl);
    }

    /**
     * Clear expired cache entries
     *
     * phpfastcache handles cleanup automatically, but this method
     * is provided for backward compatibility.
     *
     * @param  int  $hours  Unused - phpfastcache manages its own cleanup
     */
    public function cleanup(int $hours = 24): void
    {
        if (! $this->enabled) {
            return;
        }

        try {
            // phpfastcache has built-in garbage collection
            // We can trigger a manual cleanup if needed
            $this->cache->clear();
        } catch (\Exception $e) {
            // Silently fail if cleanup fails
        }
    }

    /**
     * Generate a cache key from ID and group
     *
     * @param  string  $id  Cache identifier
     * @param  string  $group  Cache group/namespace
     * @return string Normalized cache key
     */
    private function makeCacheKey(string $id, string $group): string
    {
        // Normalize group to lowercase and limit length
        $normalizedGroup = strtolower(substr($group, 0, 10));

        // Create a safe, unique cache key
        // phpfastcache accepts alphanumeric + underscore
        $hash = substr(hash('sha256', $id), 0, 16);

        return "{$normalizedGroup}_{$hash}";
    }
}
