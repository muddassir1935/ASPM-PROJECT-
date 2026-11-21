<?php
/**
 * Class SafeRedirect — where to send the user after a form action
 * (audit F4; converted to an instantiable object in Intent 003,
 * unit 006-helpers-objects, EF-4a).
 *
 * Action handlers used to redirect every edit/delete to the feed, so an
 * admin deleting from the moderation queue was thrown out of the admin
 * panel, and edits from a profile, Explore or Bookmarks landed on index.
 * Forms now post a `return` value naming the page they came from; this
 * class only ever accepts a local CampusConnect page (never an absolute
 * URL, so it cannot become an open redirect).
 */
class SafeRedirect {
    /** Pages that may be returned to, with any query string. */
    private const PAGES = 'index|explore|bookmarks|notifications|admin|post|profile';

    /** The current page as a return value, e.g. "profile.php?id=3&tab=likes". */
    public function current(): string {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $path = basename((string)parse_url($uri, PHP_URL_PATH));
        $query = (string)parse_url($uri, PHP_URL_QUERY);
        $candidate = $path . ($query !== '' ? '?' . $query : '');
        return $this->isAllowed($candidate) ? $candidate : 'index.php';
    }

    /** Hidden form field carrying the current page. */
    public function field(): string {
        return '<input type="hidden" name="return" value="' . htmlspecialchars($this->current(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Resolve a posted `return` value to a safe local target.
     * Accepts the legacy values 'index' and a bare post id ("42").
     * When $deletedPostId is given, a return to that post's own detail
     * page (which no longer exists) falls back to $default.
     */
    public function target($return, string $default = 'index.php', ?int $deletedPostId = null): string {
        $return = is_string($return) ? trim($return) : '';
        if ($return === '' || $return === 'index') {
            return $default;
        }
        if (preg_match('/^\d+$/', $return) === 1 && (int)$return > 0) {
            $return = 'post.php?id=' . (int)$return;
        }
        if (!$this->isAllowed($return)) {
            return $default;
        }
        if ($deletedPostId !== null
            && preg_match('/^post\.php\?id=(\d+)/', $return, $m) === 1
            && (int)$m[1] === $deletedPostId) {
            return $default;
        }
        return $return;
    }

    private function isAllowed(string $candidate): bool {
        // A known page name, optionally followed by a plain query string.
        // No scheme, host, slashes, backslashes or control characters.
        return preg_match('#^(' . self::PAGES . ')\.php(\?[A-Za-z0-9_\-.=&%+]*)?$#', $candidate) === 1;
    }
}
