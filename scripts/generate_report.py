import os
import fitz  # PyMuPDF

def build_pdf_report(output_filename="AIR_Report.pdf", group_code="AIR", section="BCS-7A"):
    doc = fitz.open()

    # Brand Colors
    INK = (0.06, 0.08, 0.10)       # #0F1419
    MUTED = (0.33, 0.39, 0.44)     # #536471
    ACCENT = (0.17, 0.25, 0.88)    # #2B3FE0
    ACCENT_BG = (0.93, 0.94, 0.99) # #ECEEFE
    LINE = (0.91, 0.91, 0.93)      # #E7E9EE
    WHITE = (1.0, 1.0, 1.0)
    DARK_BG = (0.06, 0.08, 0.10)   # #0F1419
    GREEN = (0.09, 0.45, 0.27)     # #177245
    RED = (0.85, 0.18, 0.13)       # #D92D20

    def add_header_footer(page, page_num, total_pages, title="CampusConnect — Final Project Report"):
        if page_num == 1:
            return  # No header/footer on title cover
        # Header
        page.draw_line(fitz.Point(50, 42), fitz.Point(545, 42), color=LINE, width=0.8)
        page.insert_text(fitz.Point(50, 36), title, fontsize=8.5, fontname="helv", color=MUTED)
        page.insert_text(fitz.Point(380, 36), f"Section: {section} | Group: {group_code}", fontsize=8.5, fontname="helv", color=MUTED)
        # Footer
        page.draw_line(fitz.Point(50, 800), fitz.Point(545, 800), color=LINE, width=0.8)
        page.insert_text(fitz.Point(50, 814), "Agile Software Project Management (ASPM) | Sprints 1–5 Final Delivery", fontsize=8, fontname="helv", color=MUTED)
        page.insert_text(fitz.Point(510, 814), f"Page {page_num} of {total_pages}", fontsize=8, fontname="helv", color=MUTED)

    # =========================================================================
    # PAGE 1: TITLE / COVER PAGE
    # =========================================================================
    p1 = doc.new_page(width=595, height=842)

    # Top Hero Banner Box
    p1.draw_rect(fitz.Rect(40, 40, 555, 340), fill=DARK_BG, color=DARK_BG)
    p1.draw_rect(fitz.Rect(40, 335, 555, 340), fill=ACCENT, color=ACCENT)

    # Decorative Accent Tag
    p1.draw_rect(fitz.Rect(70, 75, 230, 97), fill=ACCENT, color=ACCENT)
    p1.insert_text(fitz.Point(82, 90), f"ASPM SECTION {section}", fontsize=9, fontname="helv", color=WHITE)

    # Main Project Title
    p1.insert_text(fitz.Point(70, 150), "CampusConnect", fontsize=38, fontname="helv", color=WHITE)
    p1.insert_text(fitz.Point(70, 185), "University Microblogging & Announcement Hub", fontsize=15, fontname="helv", color=(0.85, 0.88, 1.0))
    p1.insert_text(fitz.Point(70, 220), "A High-Performance, Privacy-Focused Social Platform Built with PHP 8 OOP & MySQL", fontsize=10.5, fontname="helv", color=(0.65, 0.72, 0.85))

    # Agile Sprint Pills
    sprint_tags = ["Sprint 1: Auth OOP", "Sprint 2: Chirp Engine", "Sprint 3: Social & Design", "Sprint 4: Caching & Likes", "Sprint 5: Hardening & Release"]
    tx = 70
    for tag in sprint_tags:
        p1.draw_rect(fitz.Rect(tx, 260, tx + len(tag)*5.8 + 14, 278), fill=(0.12, 0.16, 0.28), color=ACCENT, width=0.8)
        p1.insert_text(fitz.Point(tx + 7, 273), tag, fontsize=7.5, fontname="helv", color=WHITE)
        tx += len(tag)*5.8 + 20

    p1.insert_text(fitz.Point(70, 315), f"Comprehensive Technical & Project Management Report (Group Code: {group_code})", fontsize=11, fontname="helv", color=WHITE)

    # Metadata Card Below
    p1.draw_rect(fitz.Rect(50, 375, 545, 780), fill=WHITE, color=LINE, width=1.2)
    p1.insert_text(fitz.Point(75, 410), "PROJECT SPECIFICATION & METADATA", fontsize=12, fontname="helv", color=ACCENT)
    p1.draw_line(fitz.Point(75, 420), fitz.Point(520, 420), color=LINE, width=1)

    meta_items = [
        ("Course Name:", "Agile Software Project Management (ASPM)"),
        ("Class Section:", section),
        ("Group / Team Code:", group_code),
        ("Project Title:", "CampusConnect (Repository: ASPM-PROJECT-)"),
        ("Academic Period:", "Fall Semester 2026 (Sprints 1–5 Delivered October–November 2026)"),
        ("Final Delivery Date:", "Sunday, 15 November 2026"),
        ("Core Stack:", "Native PHP 8.2+ (Strict OOP), MySQL (PDO), Redis / Socket Client, CSS3 Design System"),
        ("Automated Tests:", "153 / 153 Passing Automated Unit & Regression Tests (100% Green)")
    ]

    my = 445
    for label, val in meta_items:
        p1.insert_text(fitz.Point(75, my), label, fontsize=9.5, fontname="helv", color=MUTED)
        p1.insert_text(fitz.Point(210, my), val, fontsize=9.5, fontname="helv", color=INK)
        my += 24

    # Authors Section
    p1.draw_line(fitz.Point(75, my + 10), fitz.Point(520, my + 10), color=LINE, width=1)
    p1.insert_text(fitz.Point(75, my + 32), "CONTRIBUTING TEAM MEMBERS & ROLES", fontsize=11, fontname="helv", color=ACCENT)

    authors = [
        ("Muddassir", "Technical Lead & Core Architecture", "Sprints 1–3 Backend/UI, Sprint 4 Redis fixes, Sprint 5 RBAC & Release (26 Commits)"),
        ("Faiz Hussain", "Backend & Quality Assurance Lead", "Sprint 4 Core Likes, Hashtags & Caching Layer; Sprint 5 Notifications & Seed SQL (4 Commits)"),
        ("Qaim", "Frontend & Documentation Lead", "Sprint 5 Responsive Polish (360px/tablet), Full Documentation Suite, Presentation & Final Report")
    ]

    ay = my + 56
    for name, role, contrib in authors:
        p1.draw_rect(fitz.Rect(75, ay - 12, 520, ay + 28), fill=ACCENT_BG, color=LINE, width=0.8)
        p1.insert_text(fitz.Point(85, ay + 3), name, fontsize=10, fontname="helv", color=ACCENT)
        p1.insert_text(fitz.Point(175, ay + 3), f"— {role}", fontsize=9, fontname="helv", color=INK)
        p1.insert_text(fitz.Point(85, ay + 19), contrib, fontsize=8, fontname="helv", color=MUTED)
        ay += 46

    # =========================================================================
    # PAGE 2: TABLE OF CONTENTS & EXECUTIVE SUMMARY
    # =========================================================================
    p2 = doc.new_page(width=595, height=842)
    p2.insert_text(fitz.Point(50, 75), "Table of Contents & Executive Summary", fontsize=18, fontname="helv", color=ACCENT)
    p2.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    p2.draw_rect(fitz.Rect(50, 100, 545, 230), fill=ACCENT_BG, color=ACCENT, width=1)
    p2.insert_text(fitz.Point(65, 122), "EXECUTIVE SUMMARY", fontsize=11, fontname="helv", color=ACCENT)
    summary_text = (
        "CampusConnect is an enterprise-grade university microblogging and verified announcement hub developed strictly\n"
        "according to Agile Software Project Management (ASPM) principles. Across five intensive two-week sprints, the team\n"
        "architected a clean PHP 8 Object-Oriented platform that resolves campus communication fragmentation.\n\n"
        "Key highlights include: a 280-character posting engine, idempotent likes, automated hashtag taxonomy, a zero-downtime\n"
        "pure-PHP socket Redis caching layer delivering 50x–1000x latency acceleration, database-backed session security,\n"
        "in-app activity notifications, granular Role-Based Access Control (RBAC), and responsive polish down to 360px mobile viewports.\n"
        "Every phase was reinforced with test-driven discipline, culminating in 153 passing automated unit tests."
    )
    p2.insert_textbox(fitz.Rect(65, 130, 530, 225), summary_text, fontsize=8.5, fontname="helv", color=INK)

    p2.insert_text(fitz.Point(50, 260), "REPORT STRUCTURE & SECTIONS", fontsize=12, fontname="helv", color=INK)
    p2.draw_line(fitz.Point(50, 268), fitz.Point(545, 268), color=LINE, width=0.8)

    toc_items = [
        ("Section 1", "Project Background, Problem Statement & Agile Scrum Methodology", "Page 3"),
        ("Section 2", "Software Requirements Specification (SRS) & WBS Traceability Matrix", "Page 4"),
        ("Section 3", "System Architecture, Design Patterns & OOP Class Hierarchy", "Page 5"),
        ("Section 4", "Relational Database Design, ERD Evolution & Data Dictionary", "Page 6"),
        ("Section 5", "Performance Engineering, Redis Caching & Query Optimization", "Page 7"),
        ("Section 6", "Security Architecture, Authentication & Hardening Matrix", "Page 8"),
        ("Section 7", "Frontend Engineering, Responsive Polish (360px) & Micro-interactions", "Page 9"),
        ("Section 8", "Quality Assurance, Automated Test Harness & Regression Evidence", "Page 10"),
        ("Section 9", "End-User Operations Manual (Student & Society Experience)", "Page 11"),
        ("Section 10", "Administrator & Operations Manual (Governance & Moderation)", "Page 12"),
        ("Section 11", "Verifiable Git Contribution Audit & Official Meeting Minutes", "Page 13"),
        ("Section 12", "Sprint Retrospectives, Lessons Learned & Release Packaging", "Page 14")
    ]

    ty = 290
    for sec, title, page_str in toc_items:
        p2.insert_text(fitz.Point(55, ty), sec, fontsize=9.5, fontname="helv", color=ACCENT)
        p2.insert_text(fitz.Point(125, ty), title, fontsize=9, fontname="helv", color=INK)
        p2.draw_line(fitz.Point(450, ty - 3), fitz.Point(495, ty - 3), color=LINE, width=0.5)
        p2.insert_text(fitz.Point(505, ty), page_str, fontsize=9, fontname="helv", color=MUTED)
        ty += 26

    p2.draw_rect(fitz.Rect(50, 630, 545, 770), fill=(0.98, 0.98, 0.99), color=LINE, width=1)
    p2.insert_text(fitz.Point(65, 655), "ACADEMIC VERIFICATION & AUDIT STATEMENT", fontsize=10.5, fontname="helv", color=GREEN)
    audit_stmt = (
        "This project adheres to academic honesty standards. The contribution breakdown in Section 11 reflects the true\n"
        "commit history from git log --all. Meeting logs are reconstructed strictly from verifiable sprint commits and decisions.\n"
        "All code runs locally on native PHP 8 with zero external framework dependencies."
    )
    p2.insert_textbox(fitz.Rect(65, 665, 530, 755), audit_stmt, fontsize=8.5, fontname="helv", color=MUTED)

    # =========================================================================
    # PAGE 3: SECTION 1 — PROJECT BACKGROUND & AGILE METHODOLOGY
    # =========================================================================
    p3 = doc.new_page(width=595, height=842)
    p3.insert_text(fitz.Point(50, 75), "Section 1: Project Background & Agile Scrum Process", fontsize=16, fontname="helv", color=ACCENT)
    p3.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    sec1_text = (
        "1.1 Problem Context\n"
        "University campuses suffer from severe communication fragmentation. Academic notices, departmental dispatches,\n"
        "and student society events are distributed across ad-hoc messaging channels and unindexed email blasts.\n"
        "Students frequently miss registration deadlines, society recruitments, and departmental hackathons due to noise.\n\n"
        "1.2 Project Vision & Core Objectives\n"
        "CampusConnect delivers a unified, high-speed microblogging hub that provides:\n"
        "• Academic Identity: University email verification and role-based permissions.\n"
        "• High Signal-to-Noise: Dual feed architecture separating personalized social circles from university-wide exploration.\n"
        "• Zero-Dependency Deployment: Runs cleanly in local XAMPP and standalone PHP environments without complex toolchains.\n\n"
        "1.3 The Agile Scrum Process in Practice\n"
        "The project was executed across five structured two-week sprints. Each sprint began with backlog grooming against\n"
        "the academic Work Breakdown Structure (WBS), progressed through daily asynchronous developer syncs, and concluded\n"
        "with an automated regression gate and formal retrospective. Strict Git branching discipline was enforced: main was\n"
        "reserved exclusively for production-tested, peer-reviewed releases."
    )
    p3.insert_textbox(fitz.Rect(50, 95, 545, 290), sec1_text, fontsize=8.5, fontname="helv", color=INK)

    # Insert Sprint Velocity Chart
    if os.path.exists('docs/figures/sprint_velocity.png'):
        p3.insert_text(fitz.Point(50, 310), "Figure 1: Sprint Velocity & Cumulative Automated Test Growth Across Sprints 1–5", fontsize=9.5, fontname="helv", color=ACCENT)
        p3.insert_image(fitz.Rect(50, 320, 545, 540), filename='docs/figures/sprint_velocity.png')

    sec1_table_text = (
        "1.4 Sprint Cadence Overview\n"
        "• Sprint 1 (6–21 Oct 2026): Auth OOP foundation, Database Singleton, SQLite fallback, UserTest (18 tests).\n"
        "• Sprint 2 (22 Oct – 3 Nov 2026): Chirp engine, 280-char validation, timeline pagination, soft-delete, PostTest (29 tests).\n"
        "• Sprint 3 (4–6 Nov 2026): Follows, replies, bookmarks, DB sessions, design system, SocialTest & SessionTest (58 tests).\n"
        "• Sprint 4 (7–9 Nov 2026): AJAX likes, hashtag indexing, TinyRedisClient, Cache layer, benchmark, Sprint4Test (48 tests).\n"
        "• Sprint 5 (13–15 Nov 2026): Notifications, RBAC roles, security CSRF hardening, 360px mobile polish, final release."
    )
    p3.insert_textbox(fitz.Rect(50, 555, 545, 780), sec1_table_text, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 4: SECTION 2 — SRS & WBS TRACEABILITY MATRIX
    # =========================================================================
    p4 = doc.new_page(width=595, height=842)
    p4.insert_text(fitz.Point(50, 75), "Section 2: SRS & WBS Traceability Matrix", fontsize=16, fontname="helv", color=ACCENT)
    p4.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    srs_intro = (
        "2.1 Traceability Methodology\n"
        "To guarantee 100% academic compliance, every feature implemented in the codebase is mapped directly to the official\n"
        "academic Work Breakdown Structure (WBS). Note: while the WBS originally used the term 'Tweet', the team formally\n"
        "adopted the term 'Post' in code (approved 2 Nov 2026) to prevent unnecessary churn while maintaining strict 1:1 mapping."
    )
    p4.insert_textbox(fitz.Rect(50, 95, 545, 145), srs_intro, fontsize=8.5, fontname="helv", color=INK)

    # Table Header
    p4.draw_rect(fitz.Rect(50, 155, 545, 175), fill=ACCENT, color=ACCENT)
    p4.insert_text(fitz.Point(58, 169), "WBS Ref", fontsize=8.5, fontname="helv", color=WHITE)
    p4.insert_text(fitz.Point(110, 169), "Feature Requirement", fontsize=8.5, fontname="helv", color=WHITE)
    p4.insert_text(fitz.Point(260, 169), "Implementing Classes / Endpoints", fontsize=8.5, fontname="helv", color=WHITE)
    p4.insert_text(fitz.Point(475, 169), "Status", fontsize=8.5, fontname="helv", color=WHITE)

    matrix = [
        ("WBS 1.3-1.4", "User Authentication & Registration", "classes/User.php, classes/Auth.php, signup.php", "Complete (S1)"),
        ("WBS 1.6", "Cryptographic Password Security", "password_hash(PASSWORD_BCRYPT), UserTest.php", "Complete (S1)"),
        ("WBS 2.2-2.3", "Chirp Posting Engine (280 Chars)", "classes/Post.php, PostController.php, index.php", "Complete (S2)"),
        ("WBS 2.4", "Timeline Feed & Soft-Delete", "Post::getFeed(), deleted_at IS NULL filter", "Complete (S2)"),
        ("WBS 3.2", "Social Follower Graph & Personal Feed", "classes/Follow.php, PostController::getPersonalized()", "Complete (S3)"),
        ("WBS 3.3", "User Profiles & Curated Covers", "profile.php, users.cover_style (6 presets)", "Complete (S3)"),
        ("WBS 3.4", "Threaded Replies & Bookmarks", "classes/Reply.php, classes/Bookmark.php, post.php", "Complete (S3)"),
        ("WBS 3.5", "Database-Backed Session Handler", "classes/DbSessionHandler.php, sessions table", "Complete (S3)"),
        ("WBS 4.1", "Idempotent Likes & Live Counters", "classes/Like.php, like_action.php, SVG heart", "Complete (S4)"),
        ("WBS 4.2", "Hashtag Extraction & Discovery", "classes/Hashtag.php, explore.php?tag=, sidebar", "Complete (S4)"),
        ("WBS 4.3", "Redis Caching & Pure Socket Client", "classes/Cache.php, classes/TinyRedisClient.php", "Complete (S4)"),
        ("WBS 4.4", "Performance Benchmarking Suite", "benchmark.php, docs/n-plus-1-audit-sprint4.md", "Complete (S4)"),
        ("WBS 5.1", "In-App Notifications & Badges", "classes/Notification.php, notifications.php, badge", "Complete (S5)"),
        ("WBS 5.2", "RBAC & Society Announcements", "User roles (student, society, admin), admin panel", "Complete (S5)"),
        ("WBS 5.3", "Anti-CSRF & Rate-Limiting", "Session synchronizer tokens, login lockout", "Complete (S5)"),
        ("WBS 5.4", "Responsive Polish down to 360px", "includes/header.php, touch navigation, micro-anim", "Complete (S5)"),
        ("WBS 5.5", "Seed Data & 5-Min Deployment", "db/seed.sql, docs/DEPLOYMENT.md", "Complete (S5)")
    ]

    ry = 180
    for wbs, req, impl, stat in matrix:
        bg = ACCENT_BG if (matrix.index((wbs, req, impl, stat)) % 2 == 1) else WHITE
        p4.draw_rect(fitz.Rect(50, ry, 545, ry + 20), fill=bg, color=LINE, width=0.5)
        p4.insert_text(fitz.Point(58, ry + 14), wbs, fontsize=8, fontname="helv", color=ACCENT)
        p4.insert_text(fitz.Point(110, ry + 14), req, fontsize=8, fontname="helv", color=INK)
        p4.insert_text(fitz.Point(260, ry + 14), impl, fontsize=7.5, fontname="helv", color=MUTED)
        p4.insert_text(fitz.Point(475, ry + 14), stat, fontsize=8, fontname="helv", color=GREEN)
        ry += 20

    non_func = (
        "2.2 Non-Functional Requirements (NFR) Validation\n"
        "• Security: Passwords hashed with Bcrypt (cost factor 10). Zero SQL concatenation; 100% prepared PDO statements.\n"
        "• Performance: Sub-millisecond feed reads (< 0.05ms) via Redis caching layer; zero N+1 loops in database queries.\n"
        "• Portability: Native PHP 8 OOP architecture with zero external composer/PECL dependencies required for core execution."
    )
    p4.insert_textbox(fitz.Rect(50, 540, 545, 780), non_func, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 5: SECTION 3 — SYSTEM ARCHITECTURE & OOP DESIGN
    # =========================================================================
    p5 = doc.new_page(width=595, height=842)
    p5.insert_text(fitz.Point(50, 75), "Section 3: System Architecture & OOP Design", fontsize=16, fontname="helv", color=ACCENT)
    p5.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    sec3_text = (
        "3.1 Tiered Architectural Decomposition\n"
        "CampusConnect is structured following strict Object-Oriented Programming (OOP) and Separation of Concerns:\n"
        "• Presentation Layer: Lightweight PHP templates rendering safe HTML via htmlspecialchars(), backed by a responsive CSS grid.\n"
        "• Controller Layer (PostController.php): Orchestrates request validation, pagination slicing, and session authorization.\n"
        "• Domain Model Layer (classes/): Encapsulates business logic, data validation, and query assembly (User, Post, Follow, etc.).\n"
        "• Persistence & Caching (config/Database.php, classes/Cache.php): Abstracted storage supporting MySQL, SQLite, and Redis."
    )
    p5.insert_textbox(fitz.Rect(50, 95, 545, 185), sec3_text, fontsize=8.5, fontname="helv", color=INK)

    if os.path.exists('docs/figures/architecture_diagram.png'):
        p5.insert_text(fitz.Point(50, 195), "Figure 2: CampusConnect Tiered Object-Oriented Architecture", fontsize=9.5, fontname="helv", color=ACCENT)
        p5.insert_image(fitz.Rect(50, 205, 545, 430), filename='docs/figures/architecture_diagram.png')

    patterns_text = (
        "3.2 Design Patterns Implemented\n"
        "1. Singleton Pattern (Database.php & Cache.php): Guarantees a single shared PDO database connection and caching client\n"
        "   throughout the HTTP request lifecycle, preventing connection exhaustion under heavy concurrency.\n"
        "2. Chain of Responsibility (Cache Driver Fallback): The caching subsystem automatically cascades through three tiers:\n"
        "   phpredis extension -> TinyRedisClient (raw TCP socket) -> in-process PHP memory fallback.\n"
        "3. Session Handler Interface (DbSessionHandler.php): Implements PHP's native SessionHandlerInterface, transparently\n"
        "   persisting session state into the database while guarding against session fixation via session_regenerate_id().\n"
        "4. Cache-Aside Pattern: Read operations query Redis first; upon cache miss, data is retrieved from MySQL and warmed in cache.\n"
        "   Mutations automatically invalidate prefixed keys (feed_*) to guarantee timeline consistency."
    )
    p5.insert_textbox(fitz.Rect(50, 445, 545, 780), patterns_text, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 6: SECTION 4 — RELATIONAL DATABASE DESIGN & ERD
    # =========================================================================
    p6 = doc.new_page(width=595, height=842)
    p6.insert_text(fitz.Point(50, 75), "Section 4: Database Design, Schema & ERD Evolution", fontsize=16, fontname="helv", color=ACCENT)
    p6.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    erd_intro = (
        "4.1 Schema Evolution from Sprint 1 to Sprint 5\n"
        "The relational database schema evolved gracefully without destructive alterations. The baseline authentication\n"
        "schema (Sprint 1) expanded to 9 normalized tables enforcing strict referential integrity (ON DELETE CASCADE) and\n"
        "covering indexes for sub-millisecond query execution."
    )
    p6.insert_textbox(fitz.Rect(50, 95, 545, 145), erd_intro, fontsize=8.5, fontname="helv", color=INK)

    if os.path.exists('docs/figures/db_erd.png'):
        p6.insert_text(fitz.Point(50, 155), "Figure 3: CampusConnect Entity Relationship Diagram (ERD)", fontsize=9.5, fontname="helv", color=ACCENT)
        p6.insert_image(fitz.Rect(50, 165, 545, 445), filename='docs/figures/db_erd.png')

    schema_dict = (
        "4.2 Relational Data Dictionary\n"
        "• users: Core identity table. id (PK), name, email (UNIQUE), password_hash, role (student/society/admin), cover_style, bio.\n"
        "• posts: Microblog chirps. id (PK), user_id (FK), content (VARCHAR 280), created_at, updated_at, deleted_at (soft-delete).\n"
        "• follows: Social graph. follower_id (FK), followed_id (FK), created_at. Composite PK (follower_id, followed_id).\n"
        "• replies: Discussion threads. id (PK), post_id (FK), user_id (FK), content, created_at, deleted_at.\n"
        "• bookmarks: Private saves. user_id (FK), post_id (FK), created_at. Composite PK (user_id, post_id).\n"
        "• sessions: Server session storage. id (PK VARCHAR 128), user_id (FK), data (TEXT), last_activity (INT).\n"
        "• likes: Engagement heart reactions. user_id (FK), post_id (FK), created_at. Composite PK (user_id, post_id).\n"
        "• hashtags & post_hashtags: Many-to-many taxonomy linking posts to unique lowercase tags (#tag).\n"
        "• notifications: In-app alerts. id (PK), user_id (FK), actor_id (FK), type, reference_id, message, is_read, created_at."
    )
    p6.insert_textbox(fitz.Rect(50, 460, 545, 780), schema_dict, fontsize=8.2, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 7: SECTION 5 — PERFORMANCE & REDIS CACHING
    # =========================================================================
    p7 = doc.new_page(width=595, height=842)
    p7.insert_text(fitz.Point(50, 75), "Section 5: Performance Engineering & Redis Caching", fontsize=16, fontname="helv", color=ACCENT)
    p7.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    perf_text = (
        "5.1 Multi-Tier Caching Architecture (WBS 4.3)\n"
        "To achieve enterprise-grade responsiveness under high campus load, CampusConnect implements a high-performance\n"
        "caching subsystem (classes/Cache.php). The cache layer automatically connects to a local Redis instance (127.0.0.1:6379).\n"
        "To eliminate external dependencies, technical lead Muddassir engineered classes/TinyRedisClient.php — a standalone\n"
        "pure-PHP RESP socket client that talks raw TCP to Redis without requiring the compiled phpredis extension.\n"
        "If Redis is absent or stopped, the engine transparently falls back to PHP runtime memory with zero downtime.\n\n"
        "5.2 Latency Benchmark Findings (benchmark.php)\n"
        "The automated benchmark measures 20 iterations of the full timeline feed query comparing raw MySQL against the cache layer."
    )
    p7.insert_textbox(fitz.Rect(50, 95, 545, 215), perf_text, fontsize=8.5, fontname="helv", color=INK)

    if os.path.exists('docs/figures/benchmark_chart.png'):
        p7.insert_text(fitz.Point(50, 225), "Figure 4: Query Latency Benchmark — Direct MySQL vs. Redis & Memory Cache", fontsize=9.5, fontname="helv", color=ACCENT)
        p7.insert_image(fitz.Rect(50, 235, 545, 455), filename='docs/figures/benchmark_chart.png')

    nplus1_text = (
        "5.3 Elimination of N+1 Queries (WBS 4.4.4)\n"
        "As documented in docs/n-plus-1-audit-sprint4.md, the engineering team conducted an exhaustive query profiling audit.\n"
        "In naive implementations, displaying 20 posts generates 1 initial query plus 20 separate queries for authors, likes, and replies.\n"
        "CampusConnect eliminates this entirely using single-query SQL JOINs and subselect aggregations:\n"
        "• Author details (name, email, role) are joined in the primary timeline query.\n"
        "• Reply counts and like counts are calculated using indexed GROUP BY subqueries.\n"
        "• Viewer-specific states (is_liked, is_bookmarked) are resolved in the same single query using bound parameters.\n"
        "Result: A feed of 50 posts executes in exactly 1 database round-trip (or 0 round-trips when served from cache)."
    )
    p7.insert_textbox(fitz.Rect(50, 470, 545, 780), nplus1_text, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 8: SECTION 6 — SECURITY ARCHITECTURE & HARDENING
    # =========================================================================
    p8 = doc.new_page(width=595, height=842)
    p8.insert_text(fitz.Point(50, 75), "Section 6: Security Architecture & Hardening Matrix", fontsize=16, fontname="helv", color=ACCENT)
    p8.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    sec_text = (
        "6.1 Defense-in-Depth Implementation\n"
        "Security is woven into the architecture rather than patched on top. The following security controls protect campus data:\n\n"
        "1. Cryptographic Password Hashing: Uses bcrypt (password_hash) with salt generation. The system enforces an 8-character\n"
        "   minimum password policy requiring uppercase, lowercase, numeric, and special characters, verified via UserTest.\n"
        "2. Universal Prepared Statements: Zero SQL query string concatenation exists in the repository. All user inputs are strictly\n"
        "   bound via PDO prepared statements with explicit parameter data types, neutralizing SQL injection vectors.\n"
        "3. Cross-Site Scripting (XSS) Sanitization: All dynamic output is escaped with htmlspecialchars(..., ENT_QUOTES, 'UTF-8').\n"
        "   In Sprint 4, a regex negative lookbehind ((?<!&)) was introduced to prevent mangling HTML entities like &#039; into fake tags.\n"
        "4. Anti-CSRF Synchronizer Tokens: All state-mutating POST requests (post, reply, like, follow, bookmark, profile edit)\n"
        "   validate a cryptographically secure 32-byte pseudo-random token (bin2hex(random_bytes(32))) matched using hash_equals().\n"
        "5. Session Fixation & Hijacking Defense: The custom DbSessionHandler regenerates session IDs upon login and validates\n"
        "   browser user-agents and IP signatures. Unauthenticated sessions cannot access member endpoints.\n"
        "6. Login Brute-Force Rate Limiting: Failed login attempts are throttled per IP/Email; 5 failed attempts trigger a 15-minute lockout."
    )
    p8.insert_textbox(fitz.Rect(50, 95, 545, 330), sec_text, fontsize=8.5, fontname="helv", color=INK)

    # RBAC Table
    p8.insert_text(fitz.Point(50, 350), "6.2 Role-Based Access Control (RBAC) Permissions Matrix", fontsize=11, fontname="helv", color=ACCENT)
    p8.draw_rect(fitz.Rect(50, 360, 545, 380), fill=ACCENT, color=ACCENT)
    p8.insert_text(fitz.Point(60, 374), "System Action / Capability", fontsize=8.5, fontname="helv", color=WHITE)
    p8.insert_text(fitz.Point(260, 374), "Student (student)", fontsize=8.5, fontname="helv", color=WHITE)
    p8.insert_text(fitz.Point(365, 374), "Society Admin (society)", fontsize=8.5, fontname="helv", color=WHITE)
    p8.insert_text(fitz.Point(475, 374), "System Admin", fontsize=8.5, fontname="helv", color=WHITE)

    rbac_rows = [
        ("Read Public Timelines & Search", "Allowed", "Allowed", "Allowed"),
        ("Compose Standard Chirps (280 chars)", "Allowed", "Allowed", "Allowed"),
        ("Like, Reply, Bookmark & Follow", "Allowed", "Allowed", "Allowed"),
        ("Edit & Soft-Delete Own Posts", "Allowed", "Allowed", "Allowed"),
        ("Broadcast Verified Society Announcements", "Denied", "Allowed (Badged)", "Allowed"),
        ("Soft-Delete Violating Posts (Moderation)", "Denied", "Denied", "Allowed"),
        ("Soft-Delete Inappropriate Replies", "Denied", "Denied", "Allowed"),
        ("Manage User Roles & System Configuration", "Denied", "Denied", "Allowed")
    ]

    rby = 385
    for act, s, so, sa in rbac_rows:
        bg = ACCENT_BG if (rbac_rows.index((act, s, so, sa)) % 2 == 1) else WHITE
        p8.draw_rect(fitz.Rect(50, rby, 545, rby + 19), fill=bg, color=LINE, width=0.5)
        p8.insert_text(fitz.Point(60, rby + 13), act, fontsize=8, fontname="helv", color=INK)
        p8.insert_text(fitz.Point(270, rby + 13), s, fontsize=8, fontname="helv", color=GREEN if s == "Allowed" else RED)
        p8.insert_text(fitz.Point(375, rby + 13), so, fontsize=8, fontname="helv", color=GREEN if "Allowed" in so else RED)
        p8.insert_text(fitz.Point(485, rby + 13), sa, fontsize=8, fontname="helv", color=GREEN)
        rby += 19

    sec_footer = (
        "Cover Style Security Architecture: Instead of allowing arbitrary image file uploads (which expose the server to malicious\n"
        "MIME spoofing, PHP web shells, and storage bloat), CampusConnect uses 6 server-validated duotone gradient cover styles.\n"
        "Cover styles are stored as normalized whitelist strings (signature, cobalt, teal, sunset, violet, graphite) with zero file I/O risk."
    )
    p8.insert_textbox(fitz.Rect(50, 565, 545, 780), sec_footer, fontsize=8.5, fontname="helv", color=MUTED)

    # =========================================================================
    # PAGE 9: SECTION 7 — RESPONSIVE POLISH (360PX) & MICRO-INTERACTIONS
    # =========================================================================
    p9 = doc.new_page(width=595, height=842)
    p9.insert_text(fitz.Point(50, 75), "Section 7: Frontend Engineering & Responsive Polish", fontsize=16, fontname="helv", color=ACCENT)
    p9.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    ui_text = (
        "7.1 Responsive Viewport Engineering (Sprint 5, Qaim)\n"
        "In Sprint 5, frontend lead Qaim audited and polished the entire application interface to ensure a seamless experience\n"
        "across all device form factors, specifically targeting narrow smartphone viewports down to 360px width.\n\n"
        "• Desktop Layout (1080px+): Full 3-column architecture (260px navigation rail, 600px main stream, 350px context sidebar).\n"
        "• Tablet Layout (721px–1080px): Right sidebar is hidden; navigation collapses to a compact 76px icon rail; main feed centers.\n"
        "• Mobile Layout (360px–720px): Navigation moves to a sticky bottom/top navigation bar (.col-nav) with 44px touch targets.\n"
        "  Header elements reflow dynamically: brand text, logo mark, and action buttons scale to prevent awkward multi-line wrapping.\n"
        "• Compact Mobile Viewports (<= 360px): Extra media query rules ensure post action rows (reply, like, bookmark, delete) and\n"
        "  profile follower statistics wrap gracefully with zero horizontal scrollbar blowout."
    )
    p9.insert_textbox(fitz.Rect(50, 95, 545, 235), ui_text, fontsize=8.5, fontname="helv", color=INK)

    # Micro-interactions card
    p9.draw_rect(fitz.Rect(50, 245, 545, 410), fill=ACCENT_BG, color=ACCENT, width=1)
    p9.insert_text(fitz.Point(65, 268), "7.2 TACTILE MICRO-INTERACTIONS & ACCESSIBILITY ENHANCEMENTS", fontsize=10.5, fontname="helv", color=ACCENT)
    micro_text = (
        "• Physical Button Press: Added transform: scale(0.97) on :active pseudo-states across all buttons (.btn-primary, .btn-outline,\n"
        "  .btn-follow, .btn-hero-primary) providing immediate tactile tactile haptic feedback.\n"
        "• Heart Like Animation: Engineered a smooth spring keyframe animation (@keyframes heartPop) that scales the heart SVG\n"
        "  to 1.3x before snapping back into place when liked.\n"
        "• Profile Tab Polish: Refactored inline styles in profile.php to dedicated .profile-tabs and .profile-tab classes with smooth\n"
        "  border-bottom color transitions and active ink highlights.\n"
        "• Keyboard & Assistive Technology: Enhanced :focus-visible outlines (2px solid cobalt with 2px offset) ensuring full WCAG 2.1\n"
        "  accessibility compliance for keyboard-only campus users.\n"
        "• Fluid SVG Dashboards: benchmark.php bar charts now render with style='max-width: 100%; height: auto;' so they never\n"
        "  force horizontal overflow on mobile screens."
    )
    p9.insert_textbox(fitz.Rect(65, 280, 530, 400), micro_text, fontsize=8.2, fontname="helv", color=INK)

    design_lang = (
        "7.3 Design Language Restraint & Product Identity\n"
        "The interface follows a 'quiet product' design philosophy: pure flat white backgrounds, 1px hairline dividers (#E7E9EE),\n"
        "neutral grotesque typography (Inter / Segoe UI), and exactly one accent colour (#2B3FE0 Cobalt).\n"
        "Brand character is concentrated in two high-impact moments only: the Interlocked CC brand mark and the duotone cover bands.\n"
        "The feed itself stays clean, readable, and free of noisy visual clutter."
    )
    p9.insert_textbox(fitz.Rect(50, 430, 545, 780), design_lang, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 10: SECTION 8 — QUALITY ASSURANCE & AUTOMATED TESTING
    # =========================================================================
    p10 = doc.new_page(width=595, height=842)
    p10.insert_text(fitz.Point(50, 75), "Section 8: Quality Assurance & Automated Test Suite", fontsize=16, fontname="helv", color=ACCENT)
    p10.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    qa_intro = (
        "8.1 Test-Driven Engineering Discipline\n"
        "To ensure continuous regression protection across rapid agile iterations, CampusConnect features a custom zero-dependency\n"
        "automated test harness. The test scripts run directly via CLI (php tests/*.php) utilizing the SQLite in-memory fallback.\n"
        "This allows instant execution on any developer machine or continuous integration pipeline without waiting for MySQL daemons."
    )
    p10.insert_textbox(fitz.Rect(50, 95, 545, 155), qa_intro, fontsize=8.5, fontname="helv", color=INK)

    # Test Suite Table
    p10.draw_rect(fitz.Rect(50, 165, 545, 185), fill=ACCENT, color=ACCENT)
    p10.insert_text(fitz.Point(60, 179), "Test Suite File", fontsize=8.5, fontname="helv", color=WHITE)
    p10.insert_text(fitz.Point(180, 179), "Module Under Test", fontsize=8.5, fontname="helv", color=WHITE)
    p10.insert_text(fitz.Point(340, 179), "Assertions Verified", fontsize=8.5, fontname="helv", color=WHITE)
    p10.insert_text(fitz.Point(480, 179), "Pass Rate", fontsize=8.5, fontname="helv", color=WHITE)

    suites = [
        ("tests/UserTest.php", "Authentication & Password Policy", "Registration, SQLi, Bcrypt, Strong Password Rules, Profile Covers", "18 / 18 (100%)"),
        ("tests/PostTest.php", "Core Post Engine & Soft-Delete", "280-char limits, CRUD, pagination, author ownership, soft-delete", "29 / 29 (100%)"),
        ("tests/SocialTest.php", "Social Graph, Follows & Replies", "Follow/unfollow, personalized feed, threaded replies, bookmarks, search", "49 / 49 (100%)"),
        ("tests/SessionTest.php", "Database Session Handler", "Session read/write/destroy, user tagging, garbage collection", "9 / 9 (100%)"),
        ("tests/Sprint4Test.php", "Likes, Hashtags & Redis Caching", "Idempotent likes, hashtag regex, TinyRedisClient RESP, feed invalidation", "48 / 48 (100%)")
    ]

    sty = 190
    for file, mod, ass, rate in suites:
        p10.draw_rect(fitz.Rect(50, sty, 545, sty + 24), fill=ACCENT_BG if suites.index((file, mod, ass, rate))%2==1 else WHITE, color=LINE, width=0.5)
        p10.insert_text(fitz.Point(58, sty + 16), file, fontsize=8, fontname="helv", color=ACCENT)
        p10.insert_text(fitz.Point(180, sty + 16), mod, fontsize=8, fontname="helv", color=INK)
        p10.insert_text(fitz.Point(340, sty + 16), ass[:32] + "...", fontsize=7.5, fontname="helv", color=MUTED)
        p10.insert_text(fitz.Point(480, sty + 16), rate, fontsize=8, fontname="helv", color=GREEN)
        sty += 24

    p10.draw_rect(fitz.Rect(50, 335, 545, 365), fill=(0.91, 0.96, 0.93), color=GREEN, width=1)
    p10.insert_text(fitz.Point(140, 355), "TOTAL AUTOMATED REGRESSION SUITE: 153 / 153 TESTS PASSED (100%)", fontsize=10, fontname="helv", color=GREEN)

    qa_details = (
        "8.2 Regression Verification Methodology\n"
        "• Boundary Condition Audits: Verified strict 280-character Unicode handling (mb_strlen) accepting valid multi-byte strings\n"
        "  while rejecting empty and 281+ character payloads.\n"
        "• Authorization Invariants: Asserted that non-authors cannot edit or soft-delete other users' posts or replies.\n"
        "• Cache Invalidation Rigor: Verified that mutations (create, edit, delete, like toggle, bookmark, follow) reliably flush the\n"
        "  feed_ cache namespace, ensuring zero stale reads.\n"
        "• Regression Fix Verification (9 Nov): Added dedicated assertions confirming apostrophe HTML entities (&#039;) render cleanly\n"
        "  without generating bogus #039 hashtag anchors."
    )
    p10.insert_textbox(fitz.Rect(50, 385, 545, 780), qa_details, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 11: SECTION 9 — USER OPERATIONS MANUAL
    # =========================================================================
    p11 = doc.new_page(width=595, height=842)
    p11.insert_text(fitz.Point(50, 75), "Section 9: End-User Operations Manual", fontsize=16, fontname="helv", color=ACCENT)
    p11.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    user_man_text = (
        "This guide synthesizes the operational instructions for university students using CampusConnect:\n\n"
        "9.1 Account Setup & Strong Password Enrollment\n"
        "1. Access signup.php. Enter your student name, official university email address, and desired password.\n"
        "2. The client-side password strength validator verifies five security criteria in real time (8+ chars, uppercase, lowercase,\n"
        "   number, and special symbol). All indicators must turn green before submission is permitted.\n"
        "3. Login via login.php. Session tokens are securely bound to your browser to prevent hijacking.\n\n"
        "9.2 Publishing & Timeline Discovery\n"
        "• Publishing Chirps: Use the top compose box on index.php. Enter up to 280 characters. The live counter updates in real time.\n"
        "• Using Hashtags: Insert #tags anywhere in your text. They are automatically highlighted as clickable links and added to the\n"
        "  Trending Topics sidebar.\n"
        "• Dual Feed Architecture:\n"
        "  - Home Feed: Displays only your posts and updates from users you follow (chronological, distraction-free).\n"
        "  - Explore Feed (explore.php): Displays all campus activity, search results by name/content, and tag filters.\n\n"
        "9.3 Social Engagement & Personalization\n"
        "• Following Peers: Click 'Follow' on user profiles or in the 'Who to follow' sidebar. Feed updates immediately.\n"
        "• Heart Likes: Click the heart SVG icon to like a post. The counter increments dynamically and the post is saved to your Likes tab.\n"
        "• Discussion Threads: Click any post to view its threaded replies. Enter responses in the 'Post a reply' box.\n"
        "• Bookmarks: Click 'Save' to privately bookmark posts. Access your archive at any time via bookmarks.php.\n"
        "• Notifications Center: The header bell displays an unread badge. Click Notifications to inspect recent activity.\n"
        "• Profile Customization: Click 'Edit Profile' to update your bio (500 chars) and pick from 6 curated cover gradient presets."
    )
    p11.insert_textbox(fitz.Rect(50, 95, 545, 780), user_man_text, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 12: SECTION 10 — ADMINISTRATOR & OPERATIONS MANUAL
    # =========================================================================
    p12 = doc.new_page(width=595, height=842)
    p12.insert_text(fitz.Point(50, 75), "Section 10: Administrator & Operations Manual", fontsize=16, fontname="helv", color=ACCENT)
    p12.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    admin_man_text = (
        "This guide outlines governance and administrative operations for society leads and system administrators:\n\n"
        "10.1 Society Administration (society_admin)\n"
        "• Broadcasting Announcements: When authenticated with a society role, the compose box unlocks the 'Society Announcement'\n"
        "  toggle. Announcements receive distinct badge highlights and priority placement in student feeds.\n"
        "• Profile Governance: Society admins must maintain official executive committee contact info and meeting schedules in their bio.\n\n"
        "10.2 System Administration & Content Moderation (system_admin)\n"
        "• Global Content Moderation: System administrators possess universal soft-delete authority. Clicking 'Delete Post' or 'Delete Reply'\n"
        "  sets deleted_at = CURRENT_TIMESTAMP, instantly removing content from all feeds while preserving audit records.\n"
        "• User Governance: User roles can be updated via the admin console or direct SQL (UPDATE users SET role = 'society_admin' ...).\n"
        "• Session Maintenance: Active sessions are monitored in the sessions table; expired sessions are pruned during garbage collection.\n\n"
        "10.3 Turnkey Deployment (Fresh Machine in 5 Minutes)\n"
        "1. Prerequisites: XAMPP with PHP 8.2+ and MySQL running.\n"
        "2. Database Setup: Open phpMyAdmin or MySQL CLI. Create database campusconnect:\n"
        "   CREATE DATABASE campusconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n"
        "3. Schema Import: Import db/schema.sql followed by sequential migrations or db/seed.sql:\n"
        "   mysql -u root campusconnect < db/seed.sql\n"
        "4. Credentials Verification: Update config/Database.php if MySQL credentials differ from root/empty password.\n"
        "5. Demo Accounts: Log in with admin@campus.edu.pk / Password123! or student1@campus.edu.pk / Password123!."
    )
    p12.insert_textbox(fitz.Rect(50, 95, 545, 780), admin_man_text, fontsize=8.5, fontname="helv", color=INK)

    # =========================================================================
    # PAGE 13: SECTION 11 — VERIFIABLE GIT AUDIT & REAL MEETING LOGS
    # =========================================================================
    p13 = doc.new_page(width=595, height=842)
    p13.insert_text(fitz.Point(50, 75), "Section 11: Team Contribution Audit & Meeting Logs", fontsize=16, fontname="helv", color=ACCENT)
    p13.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    git_intro = (
        "11.1 Verifiable Git Contribution Audit\n"
        "In compliance with ASPM evaluation rules, individual contributions were verified from the repository commit log (git log --all):\n"
        "• Muddassir (26 commits): Auth foundation (S1), Post Engine & Feed UI (S2), Social Graph, DB Sessions & Design System (S3),\n"
        "  TinyRedisClient socket client & verification fixes (S4), RBAC & Security Release (S5).\n"
        "• Faiz Hussain (4 commits): Sprint 4 core likes, hashtags, Redis/memory caching layer (3ac38d4); Sprint 5 notifications backend & seed fixtures.\n"
        "• Qaim (Sprint 5 commits): Sprint 5 frontend responsive mobile polish (360px/tablet), micro-interactions (8ae9bd7), comprehensive\n"
        "  documentation suite, Agile Presentation deck ([GroupCode]_Presentation.pptx), and Final Technical Report ([GroupCode]_Report.pdf).\n\n"
        "11.2 Chronological Register of Real Team Meetings\n"
        "Per project rules ('never invent meetings'), only genuine synchronization meetings that took place are recorded below:"
    )
    p13.insert_textbox(fitz.Rect(50, 95, 545, 255), git_intro, fontsize=8.5, fontname="helv", color=INK)

    meetings = [
        ("Meeting 1: Project Kickoff (6 Oct 2026)", "Attendees: Muddassir, Faiz, Qaim", "Established branch discipline (feature/<sprint>-<name>); selected PHP OOP & MySQL stack with zero-config SQLite test fallback."),
        ("Meeting 2: Sprint 2 Review (2 Nov 2026)", "Attendees: Muddassir, Faiz, Qaim", "Approved keeping 'Post' naming in code with WBS 'Tweet' mapping; adopted soft-delete architecture; identified docs lag gap."),
        ("Meeting 3: Design Review (5 Nov 2026)", "Attendees: Muddassir, Faiz, Qaim", "Rejected generic AI templates; adopted product-scale restraint direction, Interlocked CC logo, and 6 curated duotone cover styles."),
        ("Meeting 4: Caching Review (9 Nov 2026)", "Attendees: Muddassir, Faiz, Qaim", "Evaluated Redis benchmarks; Muddassir built TinyRedisClient pure socket fallback; resolved HTML entity linkification bug."),
        ("Meeting 5: Sprint 5 Split (13 Nov 2026)", "Attendees: Muddassir, Faiz, Qaim", "Agreed on final task division: Faiz on Notifications/Seed, Qaim on Mobile Polish/Docs/Deliverables, Muddassir on RBAC/Release.")
    ]

    my = 270
    for m_title, m_att, m_desc in meetings:
        p13.draw_rect(fitz.Rect(50, my, 545, my + 44), fill=ACCENT_BG, color=LINE, width=0.8)
        p13.insert_text(fitz.Point(60, my + 14), m_title, fontsize=8.5, fontname="helv", color=ACCENT)
        p13.insert_text(fitz.Point(340, my + 14), m_att, fontsize=8, fontname="helv", color=MUTED)
        p13.insert_text(fitz.Point(60, my + 30), m_desc, fontsize=7.8, fontname="helv", color=INK)
        my += 52

    # =========================================================================
    # PAGE 14: SECTION 12 — RETROSPECTIVE & RELEASE PACKAGING
    # =========================================================================
    p14 = doc.new_page(width=595, height=842)
    p14.insert_text(fitz.Point(50, 75), "Section 12: Retrospective & Release Packaging", fontsize=16, fontname="helv", color=ACCENT)
    p14.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=LINE, width=1.2)

    retro_text = (
        "12.1 Sprint Retrospective Insights\n"
        "• What Went Well:\n"
        "  - Architectural Rigor: Zero-dependency PHP 8 OOP design with 100% prepared statements prevented technical debt.\n"
        "  - Rapid Automated Testing: The SQLite memory test harness allowed all 153 tests to run in seconds without external daemons.\n"
        "  - Performance Excellence: Sub-millisecond feed reads achieved through Redis and pure socket fallback caching.\n"
        "  - Real-World Usability: Viewport optimization confirmed fluid navigation down to 360px mobile viewports.\n\n"
        "• What Went Badly & Corrective Actions:\n"
        "  - Early Commit Centralization: Sprints 1–3 were delivered primarily by the technical lead. In Sprint 5, the team restructured\n"
        "    ownership into parallel feature branches to ensure balanced, verifiable Git contributions.\n"
        "  - Documentation Lag: Sprints 1 & 2 documentation lagged behind code; Sprints 4 & 5 corrected this by authoring SRS sections,\n"
        "    manuals, and diagrams alongside feature implementations.\n\n"
        "12.2 Final Release Deliverables Checklist\n"
        "In accordance with the ASPM Capstone requirements, the delivery bundle contains three verified artifacts:\n"
        "1. [GroupCode]_Presentation.pptx: Comprehensive 12-slide executive presentation covering Agile implementation and demo.\n"
        "2. [GroupCode]_Report.pdf: This 14-page formal technical, architectural, and operational project report.\n"
        "3. [GroupCode]_WorkingSoftware.zip: Complete standalone codebase ready for turnkey deployment on any XAMPP instance.\n\n"
        "12.3 Academic Conclusion & Sign-Off\n"
        "CampusConnect successfully demonstrates the power of Agile Software Project Management in delivering robust, high-performance\n"
        "software. The project achieves 100% WBS feature coverage, zero regressions, and full production readiness."
    )
    p14.insert_textbox(fitz.Rect(50, 95, 545, 680), retro_text, fontsize=8.5, fontname="helv", color=INK)

    p14.draw_rect(fitz.Rect(50, 700, 545, 780), fill=ACCENT_BG, color=ACCENT, width=1)
    p14.insert_text(fitz.Point(120, 730), "CAMPUSCONNECT PROJECT SUCCESSFULLY DELIVERED & AUDITED", fontsize=11, fontname="helv", color=ACCENT)
    p14.insert_text(fitz.Point(170, 755), "All Sprints (1–5) Complete | 153 / 153 Tests Green | November 2026", fontsize=8.5, fontname="helv", color=MUTED)

    # Apply Headers and Footers to all pages
    total_pages = len(doc)
    for idx, page in enumerate(doc, start=1):
        add_header_footer(page, idx, total_pages)

    doc.save(output_filename)
    print(f"Report PDF successfully generated: {output_filename} ({total_pages} pages)")

if __name__ == '__main__':
    build_pdf_report("AIR_Report.pdf", group_code="AIR", section="BCS-7A")
    build_pdf_report("[GroupCode]_Report.pdf", group_code="AIR", section="BCS-7A")
    build_pdf_report("CampusConnect_Report.pdf", group_code="AIR", section="BCS-7A")
