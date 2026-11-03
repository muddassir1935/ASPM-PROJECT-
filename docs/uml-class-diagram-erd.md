# UML Class Diagram and ERD (Sprints 1–2, as built)

Diagrams are drawn from the code in `main` at Sprint 2 completion
(merge `f496a09`, 2 Nov 2026). Class names, methods and columns below match
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
        +__construct(PDO)
        +register(name, email, password) bool
        +verifyPassword(email, password) array|false
    }
    class Auth {
        -static startSession()
        +static login(userData)
        +static logout()
        +static isLoggedIn() bool
        +static user() array|null
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
        +getUserFeed(userId, page, limit) array
    }
    Database --> User : provides PDO
    Database --> Post : provides PDO
    PostController --> Post : uses
    PostController --> Auth : defaults current user
    Post --> User : author via user_id
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

## Entity-relationship diagram

```mermaid
erDiagram
    users ||--o{ posts : "authors"
    users {
        int id PK
        string name
        string email UK
        string password_hash
        string role "student | admin | society"
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
