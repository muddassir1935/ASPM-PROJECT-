<?php
/**
 * Class Cache
 *
 * High-performance Caching Layer with Redis & Graceful Fallback (Sprint 4, WBS 4.3).
 * If Redis is installed and reachable, caches feed queries and counts with TTL.
 * If Redis is unavailable, transparently falls back to an in-memory/pass-through cache
 * without failing or interrupting the user request.
 */
class Cache {
    private static ?Cache $instance = null;
    private $redis = null;
    private bool $isRedisAvailable = false;
    private array $memoryCache = [];

    private function __construct() {
        $this->initRedis();
    }

    public static function getInstance(): Cache {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Attempt connection to Redis with short timeout to prevent slow request stalls.
     */
    private function initRedis(): void {
        if (class_exists('Redis')) {
            try {
                $r = new Redis();
                // 300ms connection timeout
                $connected = @$r->connect('127.0.0.1', 6379, 0.3);
                if ($connected && @$r->ping()) {
                    $this->redis = $r;
                    $this->isRedisAvailable = true;
                }
            } catch (Throwable $e) {
                // Redis is optional; fallback is silent and seamless
                $this->isRedisAvailable = false;
                $this->redis = null;
            }
        }
    }

    public function isAvailable(): bool {
        return $this->isRedisAvailable;
    }

    public function getDriverName(): string {
        return $this->isRedisAvailable ? 'Redis' : 'MemoryFallback';
    }

    /**
     * Retrieve an item from cache. Returns false or null if missing.
     */
    public function get(string $key) {
        if ($this->isRedisAvailable && $this->redis) {
            try {
                $val = $this->redis->get($key);
                if ($val !== false) {
                    return unserialize($val);
                }
                return null;
            } catch (Throwable $e) {
                // Fall through to memory cache on Redis error
            }
        }

        if (isset($this->memoryCache[$key])) {
            $item = $this->memoryCache[$key];
            if ($item['expires_at'] === 0 || $item['expires_at'] >= time()) {
                return $item['value'];
            }
            unset($this->memoryCache[$key]);
        }

        return null;
    }

    /**
     * Store an item in cache with a TTL (seconds). Default 300s (5 mins).
     */
    public function set(string $key, $value, int $ttl = 300): bool {
        if ($this->isRedisAvailable && $this->redis) {
            try {
                return (bool)$this->redis->setex($key, $ttl, serialize($value));
            } catch (Throwable $e) {
                // Fall through to memory
            }
        }

        $this->memoryCache[$key] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0
        ];
        return true;
    }

    /**
     * Delete an item from cache.
     */
    public function delete(string $key): bool {
        unset($this->memoryCache[$key]);

        if ($this->isRedisAvailable && $this->redis) {
            try {
                return (bool)$this->redis->del($key);
            } catch (Throwable $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete all keys starting with a given prefix (e.g. "feed_").
     */
    public function deleteByPrefix(string $prefix): void {
        foreach (array_keys($this->memoryCache) as $k) {
            if (strpos($k, $prefix) === 0) {
                unset($this->memoryCache[$k]);
            }
        }

        if ($this->isRedisAvailable && $this->redis) {
            try {
                $keys = $this->redis->keys($prefix . '*');
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
            } catch (Throwable $e) {
                // Ignore failure
            }
        }
    }

    /**
     * Retrieve an item or compute and cache it if not present.
     */
    public function remember(string $key, int $ttl, callable $callback) {
        $val = $this->get($key);
        if ($val !== null) {
            return $val;
        }

        $fresh = $callback();
        $this->set($key, $fresh, $ttl);
        return $fresh;
    }

    /**
     * Clear all cache entries.
     */
    public function flush(): bool {
        $this->memoryCache = [];
        if ($this->isRedisAvailable && $this->redis) {
            try {
                return (bool)$this->redis->flushDB();
            } catch (Throwable $e) {
                return false;
            }
        }
        return true;
    }
}
