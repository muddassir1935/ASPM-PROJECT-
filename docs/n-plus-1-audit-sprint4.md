# N+1 Audit — Sprint 4 (WBS 4.4.4)

Date: 9 Nov 2026. Method: every list endpoint was executed against the local
SQLite database through a PDO statement class that counts `execute()` calls,
so the numbers below are measured, not estimated.

> Correction (14 Nov 2026, Sprint 5 final pass): this audit measured the
> *model methods* only. The security audit (12 Nov) later found real
> per-row queries at the *page* level that this audit missed — the profile
> followers/following modals and the Explore people-search each called
> `Follow::isFollowing()` once per row (up to 200 queries). Finding 1 below
> is corrected accordingly, and the defect itself is fixed:
> `Follow::getFollowingIds()` fetches the viewer's following-set once and
> the pages check membership with `isset()` (`profile.php`, `explore.php`).
> The statement counts in the table above are unaffected (they measure the
> model methods, which were always single-statement).

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

1. **Model methods are N+1-free** in the feed, explore, profile, tag and
   bookmarks paths: no model method issues a query per row. Every list is a
   single statement with joins. (The page-level exception found later —
   per-row `isFollowing()` in the profile modals and Explore search — is
   described in the correction note above and is fixed, not open.)
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
