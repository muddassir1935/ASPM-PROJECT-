<?php
/**
 * Class TinyRedisCacheDriver
 *
 * Concrete CacheDriverInterface backed by TinyRedisClient (pure-PHP RESP
 * socket client, WBS 4.3.1). Second link in the cache Chain of Responsibility
 * (intent 003, units 001 & 002): tried when phpredis is unavailable, and
 * passes requests on to the next handler (MemoryCacheDriver) whenever the
 * Redis socket cannot be reached.
 */
require_once __DIR__ . '/CacheDriverInterface.php';
require_once __DIR__ . '/TinyRedisClient.php';

class TinyRedisCacheDriver implements CacheDriverInterface {
    private ?TinyRedisClient $tiny = null;
    private ?CacheDriverInterface $next = null;
    private string $namespace;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.3,
        string $namespace = 'campusconnect:',
        ?TinyRedisClient $client = null
    ) {
        $this->namespace = $namespace;
        try {
            $candidate = $client ?? new TinyRedisClient($host, $port, $timeout);
            if ($candidate->ping()) {
                $this->tiny = $candidate;
            }
        } catch (Throwable $e) {
            $this->tiny = null;
        }
    }

    public function setNext(CacheDriverInterface $next): CacheDriverInterface {
        $this->next = $next;
        return $next;
    }

    public function getNext(): ?CacheDriverInterface {
        return $this->next;
    }

    public function isAvailable(): bool {
        return $this->tiny !== null;
    }

    public function getBackendName(): string {
        if ($this->isAvailable()) {
            return 'tinyredis';
        }
        return $this->next ? $this->next->getBackendName() : 'memory';
    }

    private function ns(string $key): string {
        return $this->namespace . $key;
    }

    public function get(string $key): ?string {
        if ($this->isAvailable()) {
            try {
                $val = $this->tiny->get($this->ns($key));
                if ($val !== null) {
                    return $val;
                }
            } catch (Throwable $e) {
                // Fall through to the next handler in the chain
            }
        }
        return $this->next ? $this->next->get($key) : null;
    }

    public function set(string $key, string $value, int $ttl = 300): bool {
        if ($this->isAvailable()) {
            try {
                $nsKey = $this->ns($key);
                $ok = $ttl > 0
                    ? $this->tiny->setex($nsKey, $ttl, $value)
                    : $this->tiny->set($nsKey, $value);
                if ($ok) {
                    return true;
                }
            } catch (Throwable $e) {
                // Fall through to the next handler in the chain
            }
        }
        return $this->next ? $this->next->set($key, $value, $ttl) : false;
    }

    public function delete(string $key): bool {
        if ($this->isAvailable()) {
            try {
                $this->tiny->del([$this->ns($key)]);
            } catch (Throwable $e) {
                // Ignore and propagate to downstream handlers
            }
        }
        if ($this->next) {
            $this->next->delete($key);
        }
        return true;
    }

    public function deleteByPrefix(string $prefix): void {
        if ($this->isAvailable()) {
            $keys = $this->keysByPrefix($prefix);
            if (!empty($keys)) {
                try {
                    $this->tiny->del($keys);
                } catch (Throwable $e) {
                    // Ignore
                }
            }
        }
        if ($this->next) {
            $this->next->deleteByPrefix($prefix);
        }
    }

    public function increment(string $key): ?int {
        if ($this->isAvailable()) {
            try {
                $nsKey = $this->ns($key);
                if ($this->tiny->get($nsKey) !== null) {
                    return $this->tiny->incr($nsKey);
                }
                return null;
            } catch (Throwable $e) {
                // Fall through to the next handler in the chain
            }
        }
        return $this->next ? $this->next->increment($key) : null;
    }

    private function keysByPrefix(string $prefix): array {
        $pattern = addcslashes($this->ns($prefix), '*?[]\\') . '*';
        try {
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
        } catch (Throwable $e) {
            return [];
        }
    }
}
