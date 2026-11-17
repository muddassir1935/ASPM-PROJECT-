# API Conventions — CampusConnect

CampusConnect has no public REST API. "API" here means the in-app AJAX endpoints
and the conventions they follow.

## AJAX endpoints

- One `*_action.php` file per action at the repo root (e.g. `like_action.php`,
  `follow_action.php`, `bookmark_action.php`).
- POST only for state changes. Every request carries a CSRF token; the endpoint
  rejects the request without a valid token.
- The endpoint checks authentication and the relevant permission
  (`Auth::can(...)`) before doing anything — the UI hiding a button is never
  the security boundary.
- Response: JSON, always with a `success` boolean. On success, include the new
  state the UI needs (e.g. updated like count, new follow state). On failure,
  include a short `error` message; never leak internals (no SQL, no stack traces).
- After a state change, the page must not require a full reload to show it —
  the endpoint response drives the UI update.

## Page forms

- State-changing forms POST with a CSRF token and follow Post/Redirect/Get
  (redirect after a successful POST so a refresh cannot re-submit).
- Double-submit protection: the server must tolerate a repeated submit without
  creating a duplicate (posts are the known case — see the security audit).

## URLs

- Clean, file-based routes: `index.php` (feed), `profile.php?id=...`,
  `post.php?id=...`, `explore.php`, `hashtag.php?tag=...`, `search.php`,
  `notifications.php`, `admin.php`, `login.php`, `register.php`.
- No URL rewriting; no framework router.
