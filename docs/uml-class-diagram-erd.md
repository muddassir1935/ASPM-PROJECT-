# UML Class Diagram and ERD (Sprints 1–3, as built)

Diagrams are drawn from the code in `main` at Sprint 3 completion
(merge `7fe4dda`, 5 Nov 2026). Class names, methods and columns below match
the files; nothing here is aspirational. Sprint 1–2 content is unchanged
except where Sprint 3 extended a class (noted).

## Class diagram

```mermaid
classDiagram
    class Database {
        -static instance
        -connection
        -driver
        +static getInstance() Database
        +getConnection() PDO
        +getDriver() string
    }
    class User {
        -db PDO
        +COVER_STYLES = ['signature','cobalt','teal','sunset','violet','graphite']
        +DEFAULT_COVER_STYLE = 'signature'
        +__construct(PDO)
        +register(name, email, password) bool
        +verifyPassword(email, password) array|false
        +getById(userId) array|false
        +updateProfile(userId, name, bio, coverStyle) bool
        +searchUsers(query, excludeUserId, limit) array
        +static normalizeCoverStyle(style) string
    }
    class Auth {
        -static startSession()
        +static login(userData)
        +static logout()
        +static isLoggedIn() bool
        +static user() array|null
        -static registerDbSessionHandler()
    }
    class DbSessionHandler {
        -db PDO
        +__construct(PDO)
        +open(path, name) bool
        +close() bool
        +read(id) string|false
        +write(id, data) bool
        +destroy(id) bool
        +gc(maxLifetime) int|false
        +setUserId(sessionId, userId) bool
    }
    class Follow {
        -db PDO
        +__construct(PDO)
        +follow(followerId, followedId) bool
        +unfollow(followerId, followedId) bool
        +toggle(followerId, followedId) bool|null
        +isFollowing(followerId, followedId) bool
        +countFollowers(userId) int
        +countFollowing(userId) int
        +getFollowers(userId, limit) array
        +getFollowing(userId, limit) array
        +getSuggestions(userId, limit) array
    }
    class Reply {
        -db PDO
        +MAX_LENGTH = 280
        +MIN_LENGTH = 1
        +__construct(PDO)
        +validateContent(content) array
        +create(userId, postId, content) int|false
        +getByPostId(postId, limit, offset) array
        +delete(replyId, userId, userRole) bool
        +countByPost(postId) int
    }
    class Bookmark {
        -db PDO
        +__construct(PDO)
        +add(userId, postId) bool
        +remove(userId, postId) bool
        +toggle(userId, postId) bool|null
        +isBookmarked(userId, postId) bool
        +getBookmarkedPosts(userId, limit, offset) array
        +countByUser(userId) int
    }
    class Post {
        -db PDO
        +MAX_LENGTH = 280
        +MIN_LENGTH = 1
        +__construct(PDO)
        +validateContent(content) array
        +create(userId, content) int|false
        +getById(postId) array|false
        +getFeed(limit, offset) array
        +getPersonalizedFeed(userId, limit, offset) array
        +countPersonalized(userId) int
        +searchPosts(query, limit, offset) array
        +getByUserId(userId, limit, offset) array
        +update(postId, userId, newContent) bool
        +delete(postId, userId, userRole) bool
        +countByUser(userId) int
        +countAll() int
        +formatTimeAgo(datetime) string
    }
    class PostController {
        -postModel Post
        -currentUser array|null
        +__construct(Post, currentUser?)
        +handleCreate(content) array
        +handleUpdate(postId, newContent) array
        +handleDelete(postId) array
        +getTimelineFeed(page, limit) array
        +getPersonalizedTimelineFeed(userId, page, limit) array
        +getUserFeed(userId, page, limit) array
    }
    Database --> User : provides PDO
    Database --> Post : provides PDO
    Database --> Follow : provides PDO
    Database --> Reply : provides PDO
    Database --> Bookmark : provides PDO
    Database --> DbSessionHandler : provides PDO
    Auth --> DbSessionHandler : registers as save handler
    PostController --> Post : uses
    PostController --> Auth : defaults current user
    Post --> User : author via user_id
    Follow --> User : follower / followed via user_id
    Reply --> Post : reply to post_id
    Reply --> User : author via user_id
    Bookmark --> Post : saved post_id
    Bookmark --> User : owner via user_id
```

Notes:

- `Database` is a Singleton: one PDO connection per request, MySQL first with
  a zero-config SQLite fallback when MySQL is unreachable.
- `PostController` never builds SQL; `Post` never reads `$_SESSION` or
  `$_POST`. The HTTP layer is `index.php`, `post.php` and `post_action.php`,
  which delegate to the controller and redirect (Post/Redirect/Get).
- Authorization lives in the model: `Post::update()` checks ownership,
  `Post::delete()` allows author or admin. The UI only decides which buttons
  to show.
- Sprint 3 additions: `Follow`, `Reply` and `Bookmark` follow the same
  pattern (PDO injected, no `$_SESSION`/`$_POST` inside the model).
  `Reply::delete()` mirrors `Post::delete()` — author or admin, soft-delete.
  `Auth` registers `DbSessionHandler` as the session save handler when PDO
  is available and falls back to native PHP sessions otherwise, so login
  never breaks on a machine without a database connection.
- `User::normalizeCoverStyle()` is the whitelist gate for `cover_style`:
  any value outside `COVER_STYLES` falls back to `'signature'`.

## Entity-relationship diagram

```mermaid
erDiagram
    users ||--o{ posts : "authors"
    users ||--o{ follows : "follows / is followed by"
    users ||--o{ replies : "authors"
    users ||--o{ bookmarks : "saves"
    users ||--o{ sessions : "has"
    posts ||--o{ replies : "has"
    posts ||--o{ bookmarks : "is saved in"
    users {
        int id PK
        string name
        string email UK
        string password_hash
        string role "student | admin | society"
        string bio "TEXT NULL (Sprint 3)"
        string cover_style "signature|cobalt|teal|sunset|violet|graphite (Sprint 3)"
        timestamp created_at
    }
    posts {
        int id PK
        int user_id FK
        string content "TEXT, 1-280 chars (app rule)"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "NULL = visible"
    }
    follows {
        int follower_id PK,FK
        int followed_id PK,FK
        timestamp created_at
    }
    replies {
        int id PK
        int post_id FK
        int user_id FK
        string content "TEXT, 1-280 chars (app rule)"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "NULL = visible"
    }
    bookmarks {
        int user_id PK,FK
        int post_id PK,FK
        timestamp created_at
    }
    sessions {
        string id PK "VARCHAR(128)"
        int user_id FK "NULL until login"
        string data "TEXT"
        int last_activity "unix time, gc sweep"
    }
```

Rules the diagram implies and the code enforces:

- `posts.user_id` → `users.id` ON DELETE CASCADE (MySQL schema; SQLite
  fallback declares the same FK with `PRAGMA foreign_keys = ON`).
- `users.email` is UNIQUE (registration rejects duplicates).
- Soft-delete is a state, not a removal: a row with `deleted_at` set stays in
  the table but is filtered out of every read query.
- Indexes: `idx_posts_user_id (user_id)`, `idx_posts_created_at
  (created_at DESC)` — matching the feed (`ORDER BY created_at DESC, id
  DESC`) and per-user queries.
- Sprint 3 rules: `follows` forbids self-follow (`CHECK (follower_id <>
  followed_id)`); `follows` and `bookmarks` use composite primary keys, so
  a duplicate follow/bookmark is impossible at the database level;
  `replies.post_id` and `replies.user_id` cascade on delete, so removing a
  post or a user removes their replies; `sessions.user_id` is NULL until
  login attaches it, and `gc()` sweeps rows by `last_activity`.
