<?php
/**
 * Class MemoryCacheDriver
 *
 * Concrete CacheDriverInterface backed by an in-process PHP array with TTL
 * expiration. Terminal link in the cache Chain of Responsibility (intent 003,
 * units 001 & 002): always available so CampusConnect runs with zero external
 * dependencies when Redis is not installed or unreachable.
 */
require_once __DIR__ . '/CacheDriverInterface.php';

class MemoryCacheDriver implements CacheDriverInterface {
    /** @var array<string, array{value: string, expires_at: int}> */
    private array $store = [];
    private ?CacheDriverInterface $next = null;

    public function setNext(CacheDriverInterface $next): CacheDriverInterface {
        $this->next = $next;
        return $next;
    }

    public function getNext(): ?CacheDriverInterface {
        return $this->next;
    }

    public function isAvailable(): bool {
        return true;
    }

    public function getBackendName(): string {
        return 'memory';
    }

    public function get(string $key): ?string {
        if (isset($this->store[$key])) {
            $item = $this->store[$key];
            if ($item['expires_at'] === 0 || $item['expires_at'] >= time()) {
                return $item['value'];
            }
            unset($this->store[$key]);
        }
        return $this->next ? $this->next->get($key) : null;
    }

    public function set(string $key, string $value, int $ttl = 300): bool {
        $this->store[$key] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0
        ];
        return true;
    }

    public function delete(string $key): bool {
        unset($this->store[$key]);
        if ($this->next) {
            $this->next->delete($key);
        }
        return true;
    }

    public function deleteByPrefix(string $prefix): void {
        if ($prefix === '') {
            $this->store = [];
        } else {
            foreach (array_keys($this->store) as $k) {
                if (strpos($k, $prefix) === 0) {
                    unset($this->store[$k]);
                }
            }
        }
        if ($this->next) {
            $this->next->deleteByPrefix($prefix);
        }
    }

    public function increment(string $key): ?int {
        $current = $this->get($key);
        if ($current === null || !is_numeric($current)) {
            return $this->next ? $this->next->increment($key) : null;
        }
        $nextVal = (int)$current + 1;
        $this->store[$key]['value'] = (string)$nextVal;
        return $nextVal;
    }
}
