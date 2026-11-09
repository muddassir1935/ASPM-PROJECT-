# Sprint 4 — SRS Increment (Likes, Hashtags & Discovery, Redis/Memory Caching)

> Status: Implemented on `feature/sprint-4-core` (9 Nov 2026).
> Mapped to official WBS 4.0.
> Naming: the WBS says **Tweet**; the code says **Post**. See `docs/wbs-mapping.md`.

## 1. Purpose and scope

Sprint 4 (WBS 4.0) delivers user engagement, taxonomy, and performance optimization:
1. **Likes / Heart Reactions (WBS 4.1)**: Users can like/unlike posts via AJAX, dynamic like counters update across the UI, and users can view their liked posts via a new Likes tab on user profiles.
2. **Hashtag System & Discovery (WBS 4.2)**: Automatic hashtag extraction (`#tag`) on post creation and update, clickable hashtag rendering across feeds, tag-filtered exploration on `explore.php?tag=...`, and a "Trending Topics" widget on the right sidebar.
3. **Caching Layer with Graceful Fallback (WBS 4.3)**: High-performance caching layer (`classes/Cache.php`) utilizing Redis when present, with a transparent zero-downtime memory fallback when Redis is absent. Feed caches are intelligently invalidated upon post mutation or deletion.

Out of scope for Sprint 4 (carried forward to Sprint 5): notifications, full CSRF/security audit, realistic university seed data, and the final deployment guide.

## 2. Functional requirements

### FR-4.1 Likes and Heart Reactions (WBS 4.1)
- `Like` model in `classes/Like.php`:
  - `add($userId, $postId)`: Idempotent like creation; verifies post exists and `deleted_at IS NULL`.
  - `remove($userId, $postId)`: Removes like.
  - `toggle($userId, $postId)`: Toggles state and returns `['liked' => bool, 'count' => int]`.
  - `isLiked($userId, $postId)`: Quick boolean check.
  - `countByPost($postId)` / `countByUser($userId)`: Aggregations.
  - `getLikedPosts($userId, $limit, $offset)`: Retrieves feed of posts liked by the user.
- Endpoint: `like_action.php` handles AJAX POST with JSON response `{ success, liked, like_count, message }`.
- UI Integration:
  - Like button with animated SVG heart and live counter added to `index.php`, `explore.php`, `post.php`, and `profile.php`.
  - Profile page includes a "Likes" tab displaying posts liked by that user.
  - Soft-deleted posts are never returned in liked lists and cannot be liked.

### FR-4.2 Hashtags and Trending Topics (WBS 4.2)
- `Hashtag` model in `classes/Hashtag.php`:
  - `extractTags($content)`: Regex parser extracting `#([a-zA-Z0-9_]{1,50})` in lowercase, deduplicated.
  - `syncPostTags($postId, array $tags)`: Syncs post tags to `hashtags` and `post_hashtags` tables. Updates appropriately on post edit.
  - `renderHashtagLinks($escapedHtml)`: Safe HTML transformation converting `#tag` into `<a href="explore.php?tag=tag" class="hashtag-link">#tag</a>`.
  - `getTrending($limit)`: Aggregates top tags from active, non-deleted posts.
  - `getPostsByTag($tag, $viewerId, $limit, $offset)`: Fetches feed for tag filter.
- UI Integration:
  - `explore.php?tag=<tag>`: Filters feed directly by hashtag.
  - Right rail widget: `renderTrendingHashtagsWidget($db)` shows trending university topics with post counts.

### FR-4.3 Caching Layer with Redis & Graceful Fallback (WBS 4.3)
- `Cache` class in `classes/Cache.php` (Singleton pattern):
  - Detects if `Redis` extension and local server (`127.0.0.1:6379`) are reachable within 300ms timeout.
  - Methods: `get($key)`, `set($key, $value, $ttl)`, `delete($key)`, `deleteByPrefix($prefix)`, `remember($key, $ttl, callable)`, `flush()`.
  - Graceful Fallback: If Redis is unavailable, transparently caches in PHP runtime memory without throwing errors or breaking user requests.
  - Cache Invalidation: Calling `Post::create()`, `Post::update()`, `Post::delete()`, or `Like::toggle()` clears `feed_` prefixed caches.

## 3. Database Schema Increment

- Tables created in MySQL (`db/schema.sql`, `db/migrate_sprint4_mysql.sql`) and SQLite (`config/Database.php`):
  - `likes` (`user_id`, `post_id`, `created_at`, PK `(user_id, post_id)`).
  - `hashtags` (`id`, `tag` UNIQUE, `created_at`).
  - `post_hashtags` (`post_id`, `hashtag_id`, `created_at`, PK `(post_id, hashtag_id)`).

