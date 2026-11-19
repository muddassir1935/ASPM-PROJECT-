<?php
/**
 * Class Cache
 *
 * High-performance Caching Layer with Redis & Graceful Fallback (Sprint 4, WBS 4.3;
 * refactored in Intent 003 Units 001 & 002 to a real Chain of Responsibility over
 * CacheDriverInterface).
 *
 * Handler chain, linked in order on construction:
 *   1. PhpRedisCacheDriver  — phpredis C extension against a reachable server,
 *   2. TinyRedisCacheDriver — TinyRedisClient pure-PHP RESP socket client,
 *   3. MemoryCacheDriver    — in-process memory fallback (always available).
 *
 * Each handler tries its own backend and delegates to the next handler in the
 * chain when unavailable or on runtime failure — no string-switched if/else
 * dispatch in Cache.
 */
require_once __DIR__ . '/CacheDriverInterface.php';
require_once __DIR__ . '/PhpRedisCacheDriver.php';
require_once __DIR__ . '/TinyRedisCacheDriver.php';
require_once __DIR__ . '/MemoryCacheDriver.php';

class Cache {
    private static ?Cache $instance = null;
    private CacheDriverInterface $chainHead;

    public function __construct(?CacheDriverInterface $chainHead = null) {
        $this->chainHead = $chainHead ?? $this->buildDefaultChain();
    }

    public static function getInstance(): Cache {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function redisHost(): string {
        return getenv('CC_REDIS_HOST') ?: '127.0.0.1';
    }

    private function redisPort(): int {
        return (int)(getenv('CC_REDIS_PORT') ?: 6379);
    }

    /**
     * Namespace for every Redis key (audit C17). Override with CC_CACHE_PREFIX.
     */
    private function namespacePrefix(): string {
        $prefix = getenv('CC_CACHE_PREFIX');
        return ($prefix !== false && $prefix !== '') ? $prefix : 'campusconnect:';
    }

    /**
     * Assemble the default Chain of Responsibility:
     *   PhpRedisCacheDriver -> TinyRedisCacheDriver -> MemoryCacheDriver
     */
    private function buildDefaultChain(): CacheDriverInterface {
        $host = $this->redisHost();
        $port = $this->redisPort();
        $ns = $this->namespacePrefix();

        $phpRedis = new PhpRedisCacheDriver($host, $port, 0.3, $ns);
        $tinyRedis = new TinyRedisCacheDriver($host, $port, 0.3, $ns);
        $memory = new MemoryCacheDriver();

        $phpRedis->setNext($tinyRedis)->setNext($memory);
        return $phpRedis;
    }

    /** Expose the head of the driver chain for inspection / testing. */
    public function getDriver(): CacheDriverInterface {
        return $this->chainHead;
    }

    /** True when a real Redis driver in the chain is active (not pure memory fallback). */
    public function isAvailable(): bool {
        return $this->getBackendName() !== 'memory';
    }

    public function getDriverName(): string {
        return $this->isAvailable() ? 'Redis' : 'MemoryFallback';
    }

    /** Which concrete backend in the chain is actively serving: 'phpredis', 'tinyredis' or 'memory'. */
    public function getBackendName(): string {
        return $this->chainHead->getBackendName();
    }

    // ---- Public API (values of any serializable type) ----

    /**
     * Retrieve an item from cache. Returns null if missing.
     */
    public function get(string $key) {
        $raw = $this->chainHead->get($key);
        if ($raw === null) {
            return null;
        }
        // Audit C17: cached values are plain arrays/scalars; never
        // instantiate objects from cache data.
        $val = @unserialize($raw, ['allowed_classes' => false]);
        if ($val === false && $raw !== serialize(false)) {
            return null;
        }
        return $val;
    }

    /**
     * Store an item in cache with a TTL (seconds). Default 300s (5 mins).
     */
    public function set(string $key, $value, int $ttl = 300): bool {
        return $this->chainHead->set($key, serialize($value), $ttl);
    }

    /**
     * Delete an item from cache across the chain.
     */
    public function delete(string $key): bool {
        return $this->chainHead->delete($key);
    }

    /**
     * Delete all keys starting with a given prefix (e.g. "feed_").
     */
    public function deleteByPrefix(string $prefix): void {
        $this->chainHead->deleteByPrefix($prefix);
    }

    // ---- Cached counters (WBS 4.3 counter caching) ----
    // Counters are stored as plain integer strings (not serialize()d values)
    // so Redis INCR can maintain them atomically.

    public function getCounter(string $key): ?int {
        $raw = $this->chainHead->get($key);
        if ($raw === null || !is_numeric($raw)) {
            return null;
        }
        return (int)$raw;
    }

    public function setCounter(string $key, int $value, int $ttl = 300): void {
        $this->chainHead->set($key, (string)$value, $ttl);
    }

    /**
     * Increment a cached counter by one, ONLY if it is already cached.
     * Returns the new value, or null when the counter is not cached.
     */
    public function incrementCounter(string $key): ?int {
        return $this->chainHead->increment($key);
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
     * Clear all cache entries in this app's namespace.
     */
    public function flush(): bool {
        $this->chainHead->deleteByPrefix('');
        return true;
    }
}
