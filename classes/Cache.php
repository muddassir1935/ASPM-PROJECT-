<?php
/**
 * Class Cache
 *
 * High-performance Caching Layer with Redis & Graceful Fallback (Sprint 4, WBS 4.3).
 *
 * Backend chain, tried in order on first use:
 *   1. phpredis extension (class Redis) against a reachable server,
 *   2. TinyRedisClient (WBS 4.3.1) — a pure-PHP RESP socket client, so a real
 *      Redis server works even when the phpredis extension is not installed,
 *   3. in-process memory fallback — the app always runs, even with no Redis.
 *
 * Server address defaults to 127.0.0.1:6379 and can be overridden with the
 * CC_REDIS_HOST / CC_REDIS_PORT environment variables.
 */
require_once __DIR__ . '/TinyRedisClient.php';

class Cache {
    private static ?Cache $instance = null;
    /** @var \Redis|null phpredis client when in use */
    private $redis = null;
    private ?TinyRedisClient $tiny = null;
    /** @var string 'phpredis' | 'tinyredis' | 'memory' */
    private string $backend = 'memory';
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

    private function redisHost(): string {
        return getenv('CC_REDIS_HOST') ?: '127.0.0.1';
    }

    private function redisPort(): int {
        return (int)(getenv('CC_REDIS_PORT') ?: 6379);
    }

    /**
     * Attempt connection to Redis with short timeout to prevent slow request stalls.
     * Tries the phpredis extension first, then the TinyRedisClient socket client.
     */
    private function initRedis(): void {
        if (class_exists('Redis')) {
            try {
                $r = new Redis();
                // 300ms connection timeout
                $connected = @$r->connect($this->redisHost(), $this->redisPort(), 0.3);
                if ($connected && @$r->ping()) {
                    $this->redis = $r;
                    $this->backend = 'phpredis';
                    return;
                }
            } catch (Throwable $e) {
                // Fall through to the socket client
                $this->redis = null;
            }
        }

        try {
            $tiny = new TinyRedisClient($this->redisHost(), $this->redisPort(), 0.3);
            if ($tiny->ping()) {
                $this->tiny = $tiny;
                $this->backend = 'tinyredis';
            }
        } catch (Throwable $e) {
            // Redis is optional; fallback is silent and seamless
            $this->tiny = null;
            $this->backend = 'memory';
        }
    }

    public function isAvailable(): bool {
        return $this->backend !== 'memory';
    }

    public function getDriverName(): string {
        return $this->isAvailable() ? 'Redis' : 'MemoryFallback';
    }

    /** Which concrete backend serves requests: 'phpredis', 'tinyredis' or 'memory'. */
    public function getBackendName(): string {
        return $this->backend;
    }

    // ---- Raw backend primitives (values are raw strings at this layer) ----

    private function rawGet(string $key): ?string {
        try {
            if ($this->backend === 'phpredis' && $this->redis) {
                $val = $this->redis->get($key);
                return $val === false ? null : (string)$val;
            }
            if ($this->backend === 'tinyredis' && $this->tiny) {
                return $this->tiny->get($key);
            }
        } catch (Throwable $e) {
            // fall through: treated as a cache miss
        }
        return null;
    }

    private function rawSet(string $key, string $value, int $ttl): bool {
        try {
            if ($this->backend === 'phpredis' && $this->redis) {
                return $ttl > 0
                    ? (bool)$this->redis->setex($key, $ttl, $value)
                    : (bool)$this->redis->set($key, $value);
            }
            if ($this->backend === 'tinyredis' && $this->tiny) {
                return $ttl > 0
                    ? $this->tiny->setex($key, $ttl, $value)
                    : $this->tiny->set($key, $value);
            }
        } catch (Throwable $e) {
            // fall through to memory
        }
        return false;
    }

    private function rawDel(array $keys): int {
        if (empty($keys)) {
            return 0;
        }
        try {
            if ($this->backend === 'phpredis' && $this->redis) {
                return (int)$this->redis->del($keys);
            }
            if ($this->backend === 'tinyredis' && $this->tiny) {
                return $this->tiny->del($keys);
            }
        } catch (Throwable $e) {
            // ignore
        }
        return 0;
    }

    private function rawIncr(string $key): ?int {
        try {
            if ($this->backend === 'phpredis' && $this->redis) {
                $val = $this->redis->incr($key);
                return $val === false ? null : (int)$val;
            }
            if ($this->backend === 'tinyredis' && $this->tiny) {
                return $this->tiny->incr($key);
            }
        } catch (Throwable $e) {
            // ignore
        }
        return null;
    }

    /** All live keys starting with $prefix, via SCAN (KEYS fallback). */
    private function rawKeysByPrefix(string $prefix): array {
        $pattern = $prefix . '*';
        try {
            if ($this->backend === 'phpredis' && $this->redis) {
                $keys = [];
                $iterator = null;
                while (($batch = $this->redis->scan($iterator, $pattern, 100)) !== false) {
                    foreach ($batch as $k) {
                        $keys[] = $k;
                    }
                    if ($iterator === 0) {
                        break;
                    }
                }
                if (!empty($keys)) {
                    return $keys;
                }
                $fallback = $this->redis->keys($pattern);
                return is_array($fallback) ? $fallback : [];
            }
            if ($this->backend === 'tinyredis' && $this->tiny) {
                $keys = [];
                $cursor = 0;
                do {
                    $step = $this->tiny->scan($cursor, $pattern, 100);
                    if ($step === null) {
                        return $this->tiny->keys($pattern);
                    }
                    [$cursor, $batch] = $step;
                    foreach ($batch as $k) {
                        $keys[] = $k;
                    }
                } while ($cursor !== 0);
                return $keys;
            }
        } catch (Throwable $e) {
            // ignore
        }
        return [];
    }

    // ---- Public API (values of any serializable type) ----

    /**
     * Retrieve an item from cache. Returns false or null if missing.
     */
    public function get(string $key) {
        if ($this->isAvailable()) {
            $val = $this->rawGet($key);
            if ($val !== null) {
                return unserialize($val);
            }
            // Miss (or backend hiccup): check memory before giving up
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
        if ($this->isAvailable() && $this->rawSet($key, serialize($value), $ttl)) {
            return true;
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
        $this->rawDel([$key]);
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

        if ($this->isAvailable()) {
            $keys = $this->rawKeysByPrefix($prefix);
            if (!empty($keys)) {
                $this->rawDel($keys);
            }
        }
    }

    // ---- Cached counters (WBS 4.3 counter caching) ----
    // Counters are stored as plain integer strings (not serialize()d values)
    // so Redis INCR can maintain them atomically. A counter that is not
    // cached reads as null = "unknown", so a cold cache can never produce
    // a count that disagrees with the database.

    public function getCounter(string $key): ?int {
        if ($this->isAvailable()) {
            $raw = $this->rawGet($key);
            if ($raw !== null) {
                return is_numeric($raw) ? (int)$raw : null;
            }
        }
        if (isset($this->memoryCache[$key])) {
            $item = $this->memoryCache[$key];
            if (($item['expires_at'] === 0 || $item['expires_at'] >= time()) && is_int($item['value'])) {
                return $item['value'];
            }
        }
        return null;
    }

    public function setCounter(string $key, int $value, int $ttl = 300): void {
        if ($this->isAvailable() && $this->rawSet($key, (string)$value, $ttl)) {
            return;
        }
        $this->memoryCache[$key] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0
        ];
    }

    /**
     * Increment a cached counter by one, ONLY if it is already cached.
     * Returns the new value, or null when the counter is not cached.
     */
    public function incrementCounter(string $key): ?int {
        if ($this->isAvailable()) {
            if ($this->rawGet($key) === null) {
                return null;
            }
            return $this->rawIncr($key);
        }
        $current = $this->getCounter($key);
        if ($current === null) {
            return null;
        }
        $this->memoryCache[$key]['value'] = $current + 1;
        return $current + 1;
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
        if ($this->backend === 'phpredis' && $this->redis) {
            try {
                return (bool)$this->redis->flushDB();
            } catch (Throwable $e) {
                return false;
            }
        }
        if ($this->backend === 'tinyredis') {
            $keys = $this->rawKeysByPrefix('');
            if (!empty($keys)) {
                $this->rawDel($keys);
            }
        }
        return true;
    }
}
