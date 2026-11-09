<?php
/**
 * Class TinyRedisClient
 *
 * Minimal Redis client speaking RESP over a plain socket (Sprint 4, WBS 4.3.1).
 * Exists so the cache layer can use a real Redis server WITHOUT the phpredis
 * PHP extension: only fsockopen/streams are required.
 *
 * Supported commands: PING, GET, SET, SETEX, DEL, INCR, SCAN, KEYS.
 *
 * Failure philosophy (matches Cache): Redis is optional infrastructure, so
 * every failure is silent — methods return null/false/0 and never throw.
 * The connection is opened lazily with a short timeout so a missing or slow
 * server can never stall a page request.
 */
class TinyRedisClient {
    private string $host;
    private int $port;
    private float $timeout;
    /** @var resource|null */
    private $socket = null;
    private bool $failed = false;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, float $timeout = 0.3) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function __destruct() {
        $this->close();
    }

    public function close(): void {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    /**
     * Open the socket on first use. Once a connect fails, this client stays
     * in the failed state for its lifetime (per-request in PHP), so callers
     * do not pay the timeout repeatedly.
     */
    private function connect(): bool {
        if (is_resource($this->socket)) {
            return true;
        }
        if ($this->failed) {
            return false;
        }

        $errno = 0;
        $errstr = '';
        $sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if ($sock === false) {
            $this->failed = true;
            return false;
        }

        stream_set_timeout($sock, (int)ceil($this->timeout) + 1);
        $this->socket = $sock;
        return true;
    }

    /**
     * Send one command and return the decoded RESP reply
     * (string|int|array|null). Returns null on any transport/protocol error.
     */
    private function command(array $args) {
        if (!$this->connect()) {
            return null;
        }

        $payload = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $arg = (string)$arg;
            $payload .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }

        try {
            if (@fwrite($this->socket, $payload) === false) {
                $this->markBroken();
                return null;
            }
            return $this->readReply();
        } catch (Throwable $e) {
            $this->markBroken();
            return null;
        }
    }

    private function markBroken(): void {
        $this->failed = true;
        $this->close();
    }

    /**
     * Decode one RESP reply frame: simple string (+), error (-), integer (:),
     * bulk string ($) and array (*).
     */
    private function readReply() {
        $line = fgets($this->socket);
        if ($line === false || strlen($line) < 3) {
            $this->markBroken();
            return null;
        }

        $type = $line[0];
        $body = rtrim($line, "\r\n");

        switch ($type) {
            case '+':
                return substr($body, 1);
            case '-':
                // Server-side error (e.g. wrong command): value is unusable.
                return null;
            case ':':
                return (int)substr($body, 1);
            case '$':
                $len = (int)substr($body, 1);
                if ($len < 0) {
                    return null; // null bulk string (missing key)
                }
                $data = '';
                $remaining = $len + 2; // payload + trailing CRLF
                while ($remaining > 0) {
                    $chunk = fread($this->socket, $remaining);
                    if ($chunk === false || $chunk === '') {
                        $this->markBroken();
                        return null;
                    }
                    $data .= $chunk;
                    $remaining -= strlen($chunk);
                }
                return substr($data, 0, $len);
            case '*':
                $count = (int)substr($body, 1);
                if ($count < 0) {
                    return null;
                }
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    $items[] = $this->readReply();
                    if ($this->failed) {
                        return null;
                    }
                }
                return $items;
            default:
                $this->markBroken();
                return null;
        }
    }

    public function ping(): bool {
        return $this->command(['PING']) === 'PONG';
    }

    /** Returns the stored string, or null when missing/unreachable. */
    public function get(string $key): ?string {
        $val = $this->command(['GET', $key]);
        return is_string($val) ? $val : null;
    }

    public function set(string $key, string $value): bool {
        return $this->command(['SET', $key, $value]) === 'OK';
    }

    public function setex(string $key, int $ttl, string $value): bool {
        if ($ttl <= 0) {
            return $this->set($key, $value);
        }
        return $this->command(['SETEX', $key, $ttl, $value]) === 'OK';
    }

    public function del(array $keys): int {
        if (empty($keys)) {
            return 0;
        }
        $val = $this->command(array_merge(['DEL'], array_values($keys)));
        return is_int($val) ? $val : 0;
    }

    /** Returns the new counter value, or null on failure. */
    public function incr(string $key): ?int {
        $val = $this->command(['INCR', $key]);
        return is_int($val) ? $val : null;
    }

    /**
     * One SCAN step. Returns [nextCursor, keys[]] or null on failure.
     */
    public function scan(int $cursor, string $pattern, int $count = 100): ?array {
        $reply = $this->command(['SCAN', $cursor, 'MATCH', $pattern, 'COUNT', $count]);
        if (!is_array($reply) || count($reply) !== 2 || !is_array($reply[1])) {
            return null;
        }
        return [(int)$reply[0], $reply[1]];
    }

    /** All keys matching a glob pattern (small datasets only; Cache prefers SCAN). */
    public function keys(string $pattern): array {
        $reply = $this->command(['KEYS', $pattern]);
        return is_array($reply) ? $reply : [];
    }
}
