<?php
/**
 * Interface CacheDriverInterface
 *
 * Contract for cache backends (intent 003, unit 001-oop-foundation, EF-1;
 * unlocks the Chain of Responsibility in unit 002-cache-chain, EF-2).
 *
 * Implementations store and retrieve raw string values under un-namespaced
 * application keys; serialization and higher-level helpers (remember,
 * counters, singleton lifecycle) live in Cache.
 *
 * Concrete drivers:
 *   - PhpRedisCacheDriver   (phpredis extension)
 *   - TinyRedisCacheDriver  (pure-PHP RESP socket client)
 *   - MemoryCacheDriver     (in-process memory fallback)
 */
interface CacheDriverInterface {
    /**
     * Set the next driver in the Chain of Responsibility and return it
     * for fluent chaining: $head->setNext($second)->setNext($third).
     */
    public function setNext(CacheDriverInterface $next): CacheDriverInterface;

    /** Next driver in the chain, or null at the tail. */
    public function getNext(): ?CacheDriverInterface;

    /** Whether this concrete driver is connected and able to serve requests. */
    public function isAvailable(): bool;

    /** Concrete backend identifier: 'phpredis', 'tinyredis' or 'memory'. */
    public function getBackendName(): string;

    /** Retrieve a raw string value by key, or null on miss / failure. */
    public function get(string $key): ?string;

    /** Store a raw string value with a TTL in seconds (0 = no expiry). */
    public function set(string $key, string $value, int $ttl = 300): bool;

    /** Delete a single key. */
    public function delete(string $key): bool;

    /** Delete all keys starting with $prefix (e.g. "feed_"; "" flushes all app keys). */
    public function deleteByPrefix(string $prefix): void;

    /** Atomically increment an integer string stored at $key, or return null on miss/failure. */
    public function increment(string $key): ?int;
}
