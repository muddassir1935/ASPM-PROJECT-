<?php
/**
 * Class PhpRedisCacheDriver
 *
 * Concrete CacheDriverInterface backed by the native phpredis C extension
 * (class Redis). Head of the default cache Chain of Responsibility (intent
 * 003, units 001 & 002): when the extension is absent or the server is
 * unreachable, every operation transparently delegates to the next handler
 * in the chain.
 */
require_once __DIR__ . '/CacheDriverInterface.php';

class PhpRedisCacheDriver implements CacheDriverInterface {
    /** @var \Redis|null */
    private $redis = null;
    private ?CacheDriverInterface $next = null;
    private string $namespace;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.3,
        string $namespace = 'campusconnect:'
    ) {
        $this->namespace = $namespace;
        if (class_exists('Redis')) {
            try {
                $r = new Redis();
                $connected = @$r->connect($host, $port, $timeout);
                if ($connected && @$r->ping()) {
                    $this->redis = $r;
                }
            } catch (Throwable $e) {
                $this->redis = null;
            }
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
        return $this->redis !== null;
    }

    public function getBackendName(): string {
        if ($this->isAvailable()) {
            return 'phpredis';
        }
        return $this->next ? $this->next->getBackendName() : 'memory';
    }

    private function ns(string $key): string {
        return $this->namespace . $key;
    }

    public function get(string $key): ?string {
        if ($this->isAvailable()) {
            try {
                $val = $this->redis->get($this->ns($key));
                if ($val !== false && $val !== null) {
                    return (string)$val;
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
                    ? (bool)$this->redis->setex($nsKey, $ttl, $value)
                    : (bool)$this->redis->set($nsKey, $value);
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
                $this->redis->del([$this->ns($key)]);
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
                    $this->redis->del($keys);
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
                if ($this->redis->get($nsKey) !== false) {
                    $val = $this->redis->incr($nsKey);
                    if ($val !== false) {
                        return (int)$val;
                    }
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
        } catch (Throwable $e) {
            return [];
        }
    }
}
