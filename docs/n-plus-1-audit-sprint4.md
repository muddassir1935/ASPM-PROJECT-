# N+1 Audit — Sprint 4 (WBS 4.4.4)

Date: 9 Nov 2026. Method: every list endpoint was executed against the local
SQLite database through a PDO statement class that counts `execute()` calls,
so the numbers below are measured, not estimated.

## Results (statements per call, independent of row count)

| Call | PDO statements | Rows |
|---|---|---|
| `Post::getFeed(20)` (cache bypassed) | 1 | 20 |
| `Post::getPersonalizedFeed(user, 20)` cold | 1 | all |
| `Post::getPersonalizedFeed(user, 20)` warm cache | 0 | all |
| `Hashtag::getPostsByTag(tag, 50)` | 1 | all |
| `Hashtag::getTrending(5)` | 1 | 5 |
| `Follow::getSuggestions(user, 5)` | 1 | 5 |
| `Like::getLikedPosts(user, 50)` | 1 | all |

## Findings

1. **No PHP-level N+1 exists** in the feed, explore, profile, tag or
   bookmarks paths: no loop issues a query per row. Every list is a single
   statement with joins.
2. **Engine-level per-row cost:** feed SELECTs carry 3–4 correlated scalar
   subqueries per row (`reply_count`, `like_count`, `is_liked`,
   `is_bookmarked` — the last two were added by Sprint 4). These are indexed
   primary-key lookups and are fine at campus scale; at very large post
   volumes the natural refactor is LEFT JOINs against pre-aggregated counts.
3. **Widgets are constant-cost:** the Trending Topics and Who-to-Follow
   widgets add exactly one statement each per page render, regardless of
   page content size.
4. **Residual optimisation (not a defect):** `Hashtag::getTrending()` is not
   cached and runs on every sidebar render. It is a single indexed
   GROUP BY; caching it for 60–300s behind the existing `feed_` invalidation
   (post create/update/delete already flush that prefix, and tag syncs happen
   inside those calls) is a safe future improvement.
5. Feed caching (WBS 4.3.4) removes the feed statement entirely on warm
   hits, which is where the real win is measured in `benchmark.php`.
