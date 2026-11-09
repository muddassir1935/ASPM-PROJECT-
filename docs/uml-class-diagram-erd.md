# UML Class Diagram and ERD (Sprints 1–4, as built)

Diagrams are drawn from the code in `main` and `feature/sprint-4-core`
(Sprint 4 completion, 9 Nov 2026). Class names, methods and columns below match
the files; nothing here is aspirational.

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
    class Like {
        -db PDO
        +__construct(PDO)
        +add(userId, postId) bool
        +remove(userId, postId) bool
        +toggle(userId, postId) array|null
        +isLiked(userId, postId) bool
        +countByPost(postId) int
        +countByUser(userId) int
        +getLikedPosts(userId, limit, offset) array
        +getUsersWhoLiked(postId, limit) array
    }
    class Hashtag {
        -db PDO
        +__construct(PDO)
        +static extractTags(content) array
        +syncPostTags(postId, tags) void
        +static renderHashtagLinks(escapedHtml) string
        +getTrending(limit) array
        +getPostsByTag(tag, viewerId, limit, offset) array
    }
    class Cache {
        -static instance
        -redis
        -isRedisAvailable bool
        -memoryCache array
        +static getInstance() Cache
        +isAvailable() bool
        +getDriverName() string
        +get(key) mixed
        +set(key, value, ttl) bool
        +delete(key) bool
        +deleteByPrefix(prefix) void
        +remember(key, ttl, callback) mixed
        +flush() bool
    }
    class Post {
        -db PDO
        +MAX_LENGTH = 280
        +MIN_LENGTH = 1
        +__construct(PDO)
        +validateContent(content) array
        +create(userId, content) int|false
        +getById(postId, viewerId) array|false
        +getFeed(limit, offset, viewerId) array
        +getPersonalizedFeed(userId, limit, offset) array
        +countPersonalized(userId) int
        +searchPosts(query, limit, offset, viewerId) array
        +getByUserId(userId, limit, offset, viewerId) array
        +update(postId, userId, newContent) bool
        +delete(postId, userId, isAdmin) bool
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
    Database --> Like : provides PDO
    Database --> Hashtag : provides PDO
    Database --> DbSessionHandler : provides PDO
    Auth --> DbSessionHandler : registers as save handler
    PostController --> Post : uses
    PostController --> Auth : defaults current user
    Post --> User : author via user_id
    Post --> Hashtag : syncs tags
    Post --> Cache : invalidates & caches
    Follow --> User : follower / followed via user_id
    Reply --> Post : reply to post_id
    Reply --> User : author via user_id
    Bookmark --> Post : saved post_id
    Bookmark --> User : owner via user_id
    Like --> Post : liked post_id
    Like --> User : liker user_id
```

Notes:

- `Database` is a Singleton: one PDO connection per request, MySQL first with
  a zero-config SQLite fallback when MySQL is unreachable.
- `Cache` is a Singleton: provides Redis caching with automatic memory fallback.
- `Like` and `Hashtag` follow standard domain model OOP patterns with PDO injection.

## Entity-relationship diagram

```mermaid
erDiagram
    users ||--o{ posts : "authors"
    users ||--o{ follows : "follows / is followed by"
    users ||--o{ replies : "authors"
    users ||--o{ bookmarks : "saves"
    users ||--o{ likes : "gives"
    users ||--o{ sessions : "has"
    posts ||--o{ replies : "has"
    posts ||--o{ bookmarks : "is saved in"
    posts ||--o{ likes : "receives"
    posts ||--o{ post_hashtags : "tagged with"
    hashtags ||--o{ post_hashtags : "categorizes"
    users {
        int id PK
        string name
        string email UK
        string password_hash
        string role "student | admin | society"
        string bio "TEXT NULL"
        string cover_style "signature|cobalt|teal|sunset|violet|graphite"
        timestamp created_at
    }
    posts {
        int id PK
        int user_id FK
        string content "TEXT, 1-280 chars"
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
        string content "TEXT, 1-280 chars"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "NULL = visible"
    }
    bookmarks {
        int user_id PK,FK
        int post_id PK,FK
        timestamp created_at
    }
    likes {
        int user_id PK,FK
        int post_id PK,FK
        timestamp created_at
    }
    hashtags {
        int id PK
        string tag UK
        timestamp created_at
    }
    post_hashtags {
        int post_id PK,FK
        int hashtag_id PK,FK
        timestamp created_at
    }
    sessions {
        string id PK "VARCHAR(128)"
        int user_id FK "NULL until login"
        string data "TEXT"
        int last_activity "unix time, gc sweep"
    }
```
