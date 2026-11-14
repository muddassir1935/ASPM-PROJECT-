<?php
// Sprint 5 (H7): baseline security headers on every page using the
// shared shell. Guarded: a few entry points may have echoed already.
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer-when-downgrade');
}
// Sprint 5 (M1): never leak stack traces / file paths to the browser.
// Errors still go to the PHP error log.
ini_set('display_errors', '0');

// Sprint 5 final fix (L7): absolute "Posted on" times rendered in the
// server timezone (XAMPP defaults to UTC — 5 hours off PKT). The app,
// the team and the demo are all in Karachi, so pin it here; every page
// using the shared shell (and the CLI scripts that include it) agrees.
date_default_timezone_set('Asia/Karachi');

if(file_exists(__DIR__ . '/../classes/Auth.php')) {
    require_once __DIR__ . '/../classes/Auth.php';
}
if(file_exists(__DIR__ . '/../classes/Authorization.php')) {
    require_once __DIR__ . '/../classes/Authorization.php';
}
if(file_exists(__DIR__ . '/../classes/Csrf.php')) {
    require_once __DIR__ . '/../classes/Csrf.php';
}
$currentUser = class_exists('Auth') ? Auth::user() : null;

// Sprint 5 (H3): if MySQL is down the app runs on the SQLite fallback —
// a DIFFERENT database with its own users and posts. Say so visibly
// instead of failing logins with a bare "Invalid email or password".
// (Auth::user() above already opened the connection, so this is free.)
$isDbFallback = false;
try {
    if (class_exists('Database')) {
        $isDbFallback = Database::getInstance()->isFallback();
    }
} catch (Throwable $e) {
    $isDbFallback = false;
}

// Design v2: initial avatars take a fixed muted tone per user (like
// contact apps), so a person reads the same colour everywhere.
// Official accounts (system + society admins) get the ink tone;
// everyone else cycles six product tones.
if (!function_exists('avatarToneClass')) {
    function avatarToneClass(int $userId, string $role = ''): string {
        if (class_exists('Authorization') && Authorization::isOfficialRole($role)) {
            return ' avatar-admin';
        }
        return ' avatar-t' . (($userId % 6) + 1);
    }
}

// Sprint 5 UI polish: one plural rule for every count label, so the UI
// never says "1 posts" or "1 followers" again. Returns the word form
// only ("post"/"posts") so call sites keep their own number styling.
if (!function_exists('pluralize')) {
    function pluralize(int $count, string $singular, ?string $plural = null): string {
        return $count === 1 ? $singular : ($plural ?? $singular . 's');
    }
}

// Brand mark: a "C" drawn as one open arc, with a node plugging into
// its opening — the campus as a connected network. Colours come from
// CSS (.mark-arc / .mark-node) so the same mark works on light chrome
// and on the dark brand surfaces (.brand-mark.on-dark).
if (!function_exists('brandMarkSvg')) {
    function brandMarkSvg(string $class = ''): string {
        return '<svg class="brand-mark' . ($class !== '' ? ' ' . $class : '')
            . '" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
            . '<path class="mark-arc" d="M12.61 6.42 A6.9 6.9 0 1 0 12.61 17.58" stroke-width="2.7" stroke-linecap="round"/>'
            . '<path class="mark-arc-2" d="M11.39 6.42 A6.9 6.9 0 1 1 11.39 17.58" stroke-width="2.7" stroke-linecap="round"/>'
            . '<path class="mark-arc" d="M10.80 5.48 A6.9 6.9 0 0 1 13.08 6.79" stroke-width="2.7" stroke-linecap="round"/></svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect — University Microblogging Platform</title>
    <?php if ($currentUser && class_exists('Csrf')): ?>
    <!-- Sprint 5 CSRF: AJAX POSTs (like/follow/bookmark/notifications) read the token from here. -->
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <!-- Google Fonts: Bricolage Grotesque for display/headings,
         Public Sans for body and UI (Sprint 5 UI polish). -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Zero-Flicker Theme Initializer (Persists Dark / Light Mode) -->
    <script>
        (function() {
            try {
                var saved = localStorage.getItem('cc_theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = saved ? saved : (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    
    <style>
        /* ============================================================
           CampusConnect Dual Design System — Production Grade
           Supports both X/Twitter Deep Dark (Lights Out) and Ultra-Clean
           Corporate Light themes with multi-shade surface depth.
           ============================================================ */
        :root, [data-theme="dark"] {
            /* Dark Theme Tokens (X/Twitter Lights Out) */
            --bg-base: #000000;
            --bg-surface: #16181C;
            --bg-surface-hover: #1D1F23;
            --bg-input: #202327;
            --nav-bg: rgba(0, 0, 0, 0.82);
            --modal-bg: #16181C;
            --modal-shadow: 0 12px 40px rgba(0, 0, 0, 0.7);

            --ink: #E7E9EA;
            --ink-soft: #D1D5DB;
            --muted: #71767B;
            --faint: #536471;

            --accent: #1D9BF0;
            --accent-hover: #1A8CD8;
            --accent-tint: rgba(29, 155, 240, 0.12);
            --accent-ring: rgba(29, 155, 240, 0.35);

            --line: #2F3336;
            --border-strong: #38444D;
            --wash: #16181C;
            --field-bg: #202327;

            --danger: #F4212E;
            --danger-text: #F4212E;
            --danger-tint: rgba(244, 33, 46, 0.12);
            --danger-border: rgba(244, 33, 46, 0.3);

            --success: #00BA7C;
            --success-tint: rgba(0, 186, 124, 0.12);
            --success-border: rgba(0, 186, 124, 0.3);

            --btn-follow-bg: #EFF3F4;
            --btn-follow-color: #0F1419;
            --btn-follow-border: #EFF3F4;
            --btn-follow-hover-bg: #D7DBDC;
            --brand-arc-2: #E7E9EA;

            /* Brand signature — deep obsidian-to-cobalt */
            --brand-grad: linear-gradient(150deg, #090B0E 0%, #0F172A 52%, #1D4ED8 138%);
            --brand-dots: radial-gradient(rgba(255, 255, 255, 0.15) 1.2px, transparent 1.9px);

            /* Cover style variants (profile cover picker) — high-contrast dark tones */
            --cover-cobalt: linear-gradient(150deg, #081229 0%, #0F275A 52%, #1D9BF0 138%);
            --cover-teal: linear-gradient(150deg, #031D1B 0%, #064E3B 54%, #059669 140%);
            --cover-sunset: linear-gradient(150deg, #2A1726 0%, #701A4E 54%, #EA580C 142%);
            --cover-violet: linear-gradient(150deg, #130E2E 0%, #3B1B7D 54%, #7C3AED 140%);
            --cover-graphite: linear-gradient(150deg, #050709 0%, #16181C 55%, #38444D 145%);

            --font: 'Public Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-display: 'Bricolage Grotesque', 'Public Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

            --ease: 140ms ease;
        }

        /* Light Theme Tokens (Production X/Twitter Corporate Light) */
        [data-theme="light"] {
            --bg-base: #FFFFFF;
            --bg-surface: #F7F9FA;
            --bg-surface-hover: #EFF3F4;
            --bg-input: #EFF3F4;
            --nav-bg: rgba(255, 255, 255, 0.88);
            --modal-bg: #FFFFFF;
            --modal-shadow: 0 12px 40px rgba(15, 20, 25, 0.18);

            --ink: #0F1419;
            --ink-soft: #2E3A42;
            --muted: #536471;
            --faint: #8696A3;

            --accent: #1D9BF0;
            --accent-hover: #1A8CD8;
            --accent-tint: rgba(29, 155, 240, 0.10);
            --accent-ring: rgba(29, 155, 240, 0.25);

            --line: #EFF3F4;
            --border-strong: #CFD9DE;
            --wash: #F7F9FA;
            --field-bg: #EFF3F4;

            --danger: #D92D20;
            --danger-text: #B3261E;
            --danger-tint: #FDECEA;
            --danger-border: #F2C4C0;

            --success: #177245;
            --success-tint: #E9F6EE;
            --success-border: #BFE3CE;

            --btn-follow-bg: #0F1419;
            --btn-follow-color: #FFFFFF;
            --btn-follow-border: #0F1419;
            --btn-follow-hover-bg: #272C30;
            --brand-arc-2: #0F1419;

            --brand-grad: linear-gradient(150deg, #0F1419 0%, #1A2358 52%, #1D9BF0 138%);
            --brand-dots: radial-gradient(rgba(255, 255, 255, 0.13) 1.2px, transparent 1.9px);

            --cover-cobalt: linear-gradient(150deg, #0A1A4A 0%, #172B7A 52%, #1D9BF0 138%);
            --cover-teal: linear-gradient(150deg, #03211F 0%, #0A5C50 54%, #15935F 140%);
            --cover-sunset: linear-gradient(150deg, #33203F 0%, #8A4A63 54%, #D97E4A 142%);
            --cover-violet: linear-gradient(150deg, #181344 0%, #46309C 54%, #7B4BC4 140%);
            --cover-graphite: linear-gradient(150deg, #090C0F 0%, #2E3A42 55%, #5F7280 145%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font);
            background-color: var(--bg-base);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            font-size: 15px;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4 {
            color: var(--ink);
        }

        /* Display type — Bricolage Grotesque on the brand moments only:
           brand, hero, auth, page/section titles, profile name. */
        .brand-text, .hero-title, .auth-tagline, .auth-title,
        .page-title, .col-head, .section-title, .profile-name,
        .empty-title, .modal-head h3, .widget-title {
            font-family: var(--font-display);
        }

        a, button, input, textarea, select {
            font-family: inherit;
        }

        button {
            cursor: pointer;
        }

        ::selection {
            background: var(--accent);
            color: #FFFFFF;
        }

        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
            border-radius: 4px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ---------- Top bar ---------- */
        .navbar {
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-inner {
            max-width: 1265px;
            margin: 0 auto;
            padding: 7px 16px;
            min-height: 52px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--ink);
            flex-shrink: 0;
        }

        /* Brand mark (interlocked CC — see brandMarkSvg in header.php) */
        .brand-mark {
            width: 31px;
            height: 31px;
            flex-shrink: 0;
            display: block;
        }

        .brand-mark .mark-arc {
            stroke: var(--accent);
        }

        .brand-mark .mark-arc-2 {
            stroke: var(--ink);
        }

        .brand-mark.on-dark .mark-arc {
            stroke: #A5B4FF;
        }

        .brand-mark.on-dark .mark-arc-2 {
            stroke: #FFFFFF;
        }

        .brand-mark.mark-sm {
            width: 24px;
            height: 24px;
        }

        .brand-text {
            font-size: 1.22rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--ink);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-link {
            text-decoration: none;
            color: var(--ink);
            font-weight: 500;
            font-size: 0.97rem;
            padding: 8px 14px;
            border-radius: 999px;
            transition: background-color var(--ease);
        }

        .nav-link:hover {
            background-color: var(--wash);
        }

        .nav-link-logout {
            color: var(--muted);
        }

        .nav-link-logout:hover {
            color: var(--ink);
            background-color: var(--wash);
        }

        .btn-nav-primary {
            background-color: var(--accent);
            color: #FFFFFF !important;
            padding: 8px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.93rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color var(--ease);
        }

        .btn-nav-primary:hover {
            background-color: var(--accent-hover);
        }

        .user-tag {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--ink);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px 5px 6px;
            border-radius: 999px;
            margin: 0 2px;
        }

        .user-tag:hover {
            background-color: var(--wash);
        }

        .user-role {
            color: var(--muted);
            font-weight: 400;
        }

        /* ---------- Page frame ---------- */
        .page-content {
            flex: 1;
            max-width: 1265px;
            width: 100%;
            margin: 0 auto;
        }

        .narrow-wrap {
            max-width: 600px;
            margin: 0 auto;
            border-left: 1px solid var(--line);
            border-right: 1px solid var(--line);
            padding-bottom: 3rem;
            min-height: 70vh;
        }

        .page-title {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            padding: 14px 16px 12px;
        }

        .col-head {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            padding: 13px 16px 11px;
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 52px;
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 30;
        }

        .section-title {
            font-size: 1.08rem;
            font-weight: 700;
            padding: 16px 16px 8px;
        }

        .back-link {
            display: inline-block;
            color: var(--ink);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.93rem;
            padding: 14px 16px 4px;
        }

        .back-link:hover {
            color: var(--accent);
        }

        /* ---------- Buttons ---------- */
        .btn-primary {
            background: var(--accent);
            color: #FFFFFF;
            border: 1px solid var(--accent);
            padding: 7px 20px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: background-color var(--ease);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
        }

        /* Disabled reads as a sleek translucent accent pill */
        .btn-primary:disabled {
            background: rgba(29, 155, 240, 0.45);
            border-color: transparent;
            color: rgba(255, 255, 255, 0.6);
            cursor: not-allowed;
        }

        .btn-outline {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--border-strong);
            padding: 6px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: background-color var(--ease), border-color var(--ease);
        }

        .btn-outline:hover {
            background: rgba(231, 233, 234, 0.08);
            border-color: #536471;
        }

        .btn-cancel {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--border-strong);
            padding: 7px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: background-color var(--ease);
        }

        .btn-cancel:hover {
            background: rgba(231, 233, 234, 0.08);
        }

        .btn-danger-outline {
            background: transparent;
            color: var(--danger-text);
            border: 1px solid var(--danger-border);
            padding: 6px 16px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.87rem;
            transition: background-color var(--ease);
        }

        .btn-danger-outline:hover {
            background: var(--danger-tint);
        }

        .btn-text {
            background: none;
            border: none;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.87rem;
            padding: 6px 10px;
            border-radius: 999px;
            transition: background-color var(--ease), color var(--ease);
        }

        .btn-text:hover {
            color: var(--accent);
            background: var(--accent-tint);
        }

        .btn-text-danger:hover {
            color: var(--danger-text);
            background: var(--danger-tint);
        }

        /* Theme toggle button (Sun/Moon icon pill) */
        .btn-theme-toggle {
            background: transparent;
            border: 1px solid var(--line);
            color: var(--muted);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color var(--ease), background var(--ease), border-color var(--ease), transform 120ms ease;
        }

        .btn-theme-toggle:hover {
            color: var(--ink);
            background: var(--bg-surface-hover);
            border-color: var(--border-strong);
            transform: scale(1.05);
        }

        .btn-theme-toggle:active {
            transform: scale(0.95);
        }

        .btn-theme-toggle svg {
            width: 19px;
            height: 19px;
        }

        [data-theme="dark"] .theme-icon-moon { display: none; }
        [data-theme="dark"] .theme-icon-sun { display: block; }
        [data-theme="light"] .theme-icon-sun { display: none; }
        [data-theme="light"] .theme-icon-moon { display: block; }

        /* Follow toggle (AJAX — Adapts to Light and Dark Modes) */
        .btn-follow {
            background: var(--btn-follow-bg);
            color: var(--btn-follow-color);
            border: 1px solid var(--btn-follow-border);
            padding: 7px 18px;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.88rem;
            flex-shrink: 0;
            transition: background-color var(--ease), color var(--ease), border-color var(--ease), transform 120ms ease;
        }

        .btn-follow:hover:not(:disabled) {
            background: var(--btn-follow-hover-bg);
            border-color: var(--btn-follow-hover-bg);
        }

        .btn-follow.following {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--border-strong);
        }

        .btn-follow.following:hover:not(:disabled) {
            background: var(--danger-tint);
            color: var(--danger-text);
            border-color: var(--danger-border);
        }

        .btn-follow:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        /* Bookmark toggle: the footer JS replaces this button's text
           ("Save"/"Saved"), so the icon is a ::before background that
           survives text replacement. */
        .btn-bookmark {
            background: none;
            border: none;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.87rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            transition: background-color var(--ease), color var(--ease);
        }

        .btn-bookmark::before {
            content: "";
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23536471' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3.5h10V20l-5-3.8L7 20Z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
        }

        .btn-bookmark:hover:not(:disabled) {
            color: var(--accent);
            background: var(--accent-tint);
        }

        .btn-bookmark:hover:not(:disabled)::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232B3FE0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3.5h10V20l-5-3.8L7 20Z'/%3E%3C/svg%3E");
        }

        /* Sprint 4 polish: filled bookmark icon when saved */
        .btn-bookmark.is-bookmarked,
        .btn-bookmark[data-bookmarked="1"] {
            color: var(--accent);
        }

        .btn-bookmark.is-bookmarked::before,
        .btn-bookmark[data-bookmarked="1"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232B3FE0' stroke='%232B3FE0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3.5h10V20l-5-3.8L7 20Z'/%3E%3C/svg%3E");
        }

        .btn-bookmark:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        /* ---------- Forms ---------- */
        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 0.97rem;
            border: 1px solid var(--border-strong);
            border-radius: 8px;
            background-color: var(--bg-input);
            color: var(--ink);
            transition: border-color var(--ease), box-shadow var(--ease);
            outline: none;
        }

        .form-control:focus {
            background-color: var(--bg-base);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-ring);
        }

        .form-control::placeholder {
            color: var(--faint);
        }

        textarea.form-control {
            resize: vertical;
            line-height: 1.5;
        }

        .char-counter {
            font-size: 0.83rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        /* Search fields read as one soft pill, not a bordered box */
        .search-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .search-row .form-control {
            border-radius: 999px;
            background-color: var(--field-bg);
            border: 1px solid transparent;
            color: var(--ink);
            padding: 9px 16px;
            font-size: 0.93rem;
        }

        .search-row .form-control:focus {
            background-color: var(--bg-base);
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-ring);
        }

        /* ---------- Alerts / flash ---------- */
        .alert {
            padding: 11px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1rem;
            border: 1px solid;
        }

        .col-main .alert, .narrow-wrap .alert {
            margin: 12px 16px;
        }

        .alert-error {
            background-color: var(--danger-tint);
            color: #8C1D18;
            border-color: var(--danger-border);
        }

        .alert-success {
            background-color: var(--success-tint);
            color: #14532D;
            border-color: var(--success-border);
        }

        /* ---------- Auth: brand panel + form, split on desktop ---------- */
        .auth-shell {
            max-width: 940px;
            margin: 2.75rem auto;
            width: 100%;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 5fr 6fr;
            align-items: stretch;
        }

        .auth-brand {
            background: var(--brand-grad);
            border-radius: 20px 0 0 20px;
            color: #FFFFFF;
            padding: 2.1rem 2.1rem 2.3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .auth-brand::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: var(--brand-dots);
            background-size: 20px 20px;
            pointer-events: none;
        }

        /* Ghost mark: the logo at poster size, barely there — gives
           the tall panel (signup) a centre instead of a void.
           Sprint 5 UI polish (M-14): centered at 225px it sat directly
           behind the tagline and its strokes crossed the words. Moved to
           the top-right corner, smaller and fainter, clear of all text. */
        .auth-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M12.61 6.42 A6.9 6.9 0 1 0 12.61 17.58' stroke='%23FFFFFF' stroke-width='2.7' stroke-linecap='round'/%3E%3Cpath d='M11.39 6.42 A6.9 6.9 0 1 1 11.39 17.58' stroke='%23FFFFFF' stroke-width='2.7' stroke-linecap='round'/%3E%3Cpath d='M10.80 5.48 A6.9 6.9 0 0 1 13.08 6.79' stroke='%23FFFFFF' stroke-width='2.7' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 28px top 28px;
            background-size: 160px 160px;
            opacity: 0.10;
            pointer-events: none;
        }

        .auth-brand-top {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .auth-brand-name {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .auth-brand-bottom {
            position: relative;
            z-index: 1;
        }

        .auth-tagline {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 1.4rem;
        }

        .auth-points {
            list-style: none;
        }

        .auth-points li {
            display: flex;
            align-items: baseline;
            gap: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.88);
            padding: 7px 0;
        }

        .auth-points li::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #A5B4FF;
            flex-shrink: 0;
            transform: translateY(-1px);
        }

        .auth-card {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            padding: 2.2rem 2.2rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-shell .auth-card {
            border-radius: 0 20px 20px 0;
            border-left: none;
        }

        .auth-header {
            margin-bottom: 1.6rem;
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .auth-subtitle {
            color: var(--muted);
            font-size: 0.93rem;
            margin-top: 5px;
        }

        .btn-submit {
            width: 100%;
            padding: 11px;
            background-color: var(--accent);
            color: #FFFFFF;
            border: 1px solid var(--accent);
            border-radius: 999px;
            font-size: 1rem;
            font-weight: 700;
            transition: background-color var(--ease);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
            border-color: var(--accent-hover);
        }

        .auth-footer-text {
            margin-top: 1.4rem;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .auth-footer-text a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer-text a:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .pw-rules {
            list-style: none;
            margin-top: 8px;
            font-size: 0.83rem;
            color: var(--muted);
            line-height: 1.7;
        }

        .field-hint {
            font-size: 0.83rem;
            margin-top: 6px;
            color: var(--muted);
            min-height: 1em;
        }

        .js-error {
            display: none;
            background: var(--danger-tint);
            color: #8C1D18;
            border: 1px solid var(--danger-border);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        /* ---------- 3-column layout: rails split by hairlines ---------- */
        .layout-3col {
            display: grid;
            grid-template-columns: 260px minmax(0, 600px) minmax(0, 350px);
            justify-content: center;
            align-items: start;
        }

        .col-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: sticky;
            top: 52px;
            height: calc(100vh - 52px);
            padding: 12px 14px 20px;
        }

        .nav-col-link {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--ink);
            font-weight: 500;
            font-size: 1.2rem;
            padding: 11px 18px 11px 14px;
            border-radius: 9999px;
            transition: background-color var(--ease), color var(--ease);
            align-self: flex-start;
        }

        .nav-col-link svg {
            width: 26px;
            height: 26px;
            flex-shrink: 0;
            stroke-width: 2;
        }

        .nav-col-link:hover {
            background-color: var(--bg-surface-hover);
            color: var(--ink);
        }

        /* Active page */
        .nav-col-active {
            font-weight: 800;
            color: var(--ink);
        }

        .btn-left-post {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent);
            color: #FFFFFF !important;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 800;
            padding: 13px 20px;
            border-radius: 9999px;
            margin-top: 14px;
            width: 88%;
            box-shadow: 0 4px 14px rgba(29, 155, 240, 0.3);
            transition: background-color var(--ease), transform 120ms ease, box-shadow var(--ease);
        }

        .btn-left-post:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(29, 155, 240, 0.4);
        }

        .btn-left-post:active {
            transform: translateY(1px);
        }

        .nav-user-dock {
            margin-top: auto;
            padding-top: 1.5rem;
            width: 100%;
        }

        .nav-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 9999px;
            text-decoration: none;
            color: var(--ink);
            transition: background-color var(--ease);
            width: 100%;
        }

        .nav-user-pill:hover {
            background: var(--bg-surface-hover);
        }

        .nav-user-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
        }

        .nav-user-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nav-user-handle {
            font-size: 0.8rem;
            color: var(--muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nav-user-dots {
            width: 18px;
            height: 18px;
            color: var(--muted);
            flex-shrink: 0;
        }

        .col-main {
            border-left: 1px solid var(--line);
            border-right: 1px solid var(--line);
            min-height: calc(100vh - 53px);
            padding-bottom: 2.5rem;
        }

        .col-side {
            position: sticky;
            top: 52px;
            padding: 12px 0 2rem 24px;
            max-height: calc(100vh - 52px);
            overflow-y: auto;
        }

        /* Search Pill Container */
        .search-pill-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: 9999px;
            padding: 10px 18px;
            margin-bottom: 16px;
            transition: background-color var(--ease), border-color var(--ease), box-shadow var(--ease);
        }

        .search-pill-wrap:focus-within {
            background: var(--bg-base);
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent);
        }

        .search-pill-icon {
            width: 18px;
            height: 18px;
            color: var(--muted);
            flex-shrink: 0;
        }

        .search-pill-wrap:focus-within .search-pill-icon {
            color: var(--accent);
        }

        .search-pill-input {
            background: transparent;
            border: none;
            outline: none;
            color: var(--ink);
            font-size: 0.95rem;
            width: 100%;
        }

        .search-pill-input::placeholder {
            color: var(--muted);
        }

        /* ---------- Right-rail widgets: elevated cards ---------- */
        .widget {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .col-side .widget:first-child {
            padding-top: 14px;
        }

        .widget-title {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: var(--ink);
            padding: 0 0 8px 0;
            margin-bottom: 4px;
        }

        .widget-empty {
            font-size: 0.88rem;
            color: var(--muted);
            padding: 4px 0 8px;
        }

        .widget-more {
            display: block;
            margin: 8px -16px -14px;
            padding: 12px 16px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border-top: 1px solid var(--line);
            transition: background var(--ease);
        }

        .widget-more:hover {
            background: var(--bg-surface-hover);
            text-decoration: none;
        }

        .widget .search-row {
            padding: 4px 16px 12px;
        }

        /* ---------- User rows ---------- */
        .user-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 16px;
            margin: 0 -16px;
            transition: background-color var(--ease);
        }

        .user-row:hover {
            background-color: var(--bg-surface-hover);
        }

        .modal-box .user-row, .list-card .user-row {
            margin: 0 -8px;
        }

        .user-row-link {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: var(--ink);
            min-width: 0;
        }

        .user-row-link:hover .user-row-text strong {
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* Sprint 5 UI polish (M-17): the column needs min-width: 0 to shrink
           inside the flex row, and the ellipsis must live on the text lines
           themselves — on the flex container it never applied, so long names
           cut mid-word and crowded the Follow button. */
        .user-row-text {
            display: flex;
            flex-direction: column;
            font-size: 0.95rem;
            line-height: 1.3;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-row-text strong {
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        .user-row-text span {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 400;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        /* ---------- Avatars: initials, tone fixed per user ---------- */
        .avatar-xs, .avatar-sm, .avatar-md, .avatar-lg {
            border-radius: 50%;
            background: var(--accent);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .avatar-xs { width: 28px; height: 28px; font-size: 0.75rem; }
        .avatar-sm { width: 36px; height: 36px; font-size: 0.88rem; }
        .avatar-md { width: 40px; height: 40px; font-size: 0.97rem; }
        .avatar-lg { width: 76px; height: 76px; font-size: 1.9rem; }

        /* Lively but not neon — contact-app saturation. Avatars carry
           most of the feed's colour, so muddy tones wash out the page. */
        .avatar-t1 { background: #1D6FD1; }
        .avatar-t2 { background: #15935F; }
        .avatar-t3 { background: #C75B1F; }
        .avatar-t4 { background: #7B4BC4; }
        .avatar-t5 { background: #CE2F62; }
        .avatar-t6 { background: #0B8585; }

        .avatar-admin {
            background: var(--ink);
        }

        /* ---------- Post rows: no cards, hairline-separated ---------- */
        .post-card {
            display: flex;
            gap: 12px;
            padding: 13px 16px 9px;
            border-bottom: 1px solid var(--line);
            transition: background-color var(--ease);
        }

        .post-card:hover {
            background-color: var(--wash);
        }

        .post-body {
            flex: 1;
            min-width: 0;
        }

        .announce-line {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 3px;
        }

        .announce-line svg {
            width: 14px;
            height: 14px;
        }

        .post-card-head {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            position: relative;
        }

        .post-card-author {
            font-weight: 700;
            color: var(--ink);
            font-size: 0.97rem;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .post-card-author:hover {
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .post-card-handle {
            color: var(--muted);
            font-size: 0.88rem;
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .post-card-time {
            font-size: 0.88rem;
            color: var(--muted);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .post-menu-wrap {
            margin-left: auto;
            position: relative;
        }

        .post-menu-btn {
            background: transparent;
            border: none;
            color: var(--muted);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color var(--ease), background-color var(--ease);
        }

        .post-menu-btn:hover {
            color: var(--accent);
            background-color: var(--accent-tint);
        }

        .post-menu-btn svg {
            width: 17px;
            height: 17px;
        }

        .post-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 60;
            min-width: 160px;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: var(--modal-shadow);
            padding: 5px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .post-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--ink);
            font-size: 0.88rem;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            transition: background-color var(--ease);
        }

        .post-dropdown-item:hover {
            background: var(--bg-surface-hover);
        }

        .post-dropdown-item svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .post-dropdown-item.item-danger {
            color: var(--danger-text);
        }

        .post-dropdown-item.item-danger:hover {
            background: var(--danger-tint);
        }

        .post-card-content {
            font-size: 0.97rem;
            color: var(--ink);
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-top: 1px;
        }

        .post-card-actions {
            display: flex;
            gap: 2px;
            align-items: center;
            margin-top: 6px;
        }

        .post-card-actions .spacer {
            flex: 1;
        }

        /* Reply affordance: muted icon + count, accent on hover */
        /* Sprint 5 UI polish (M-13): the tap target was the 32px icon circle.
           min 44x44 keeps the icon the same size and grows only the hit area. */
        .action-reply {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.83rem;
            padding: 4px 10px 4px 4px;
            min-height: 44px;
            min-width: 44px;
            border-radius: 999px;
            transition: color var(--ease);
        }

        .action-reply .action-ic {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: background-color var(--ease);
        }

        .action-reply svg {
            width: 19px;
            height: 19px;
            stroke-width: 2.05;
        }

        .action-reply:hover {
            color: var(--accent);
        }

        .action-reply:hover .action-ic {
            background: var(--accent-tint);
        }

        /* Sprint 4: Like Action (WBS 4.1) */
        /* Sprint 5 UI polish (M-13): same 44x44 hit area as .action-reply. */
        .action-like {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.83rem;
            padding: 4px 10px 4px 4px;
            min-height: 44px;
            min-width: 44px;
            border-radius: 999px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: color var(--ease);
        }

        .action-like .action-ic {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: background-color var(--ease), transform 0.15s ease;
        }

        .action-like svg {
            width: 19px;
            height: 19px;
            stroke-width: 2.05;
            transition: fill 0.2s ease, stroke 0.2s ease;
        }

        .action-like:hover {
            color: #e0245e;
        }

        .action-like:hover .action-ic {
            background: rgba(224, 36, 94, 0.1);
            transform: scale(1.08);
        }

        .action-like.is-liked {
            color: #e0245e;
        }

        .action-like.is-liked svg {
            fill: #e0245e;
            stroke: #e0245e;
        }

        /* Sprint 4: Hashtag Links & Trending (WBS 4.2) */
        .hashtag-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .hashtag-link:hover {
            text-decoration: underline;
        }

        .trending-row {
            margin: 0 -16px;
        }

        .trending-link {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            padding: 9px 16px;
            transition: background var(--ease);
        }

        .trending-link:hover {
            background: var(--bg-surface-hover);
        }

        .trending-sub {
            font-size: 0.76rem;
            font-weight: 500;
            color: var(--muted);
            letter-spacing: 0.01em;
            margin-bottom: 2px;
        }

        .trending-tag {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--ink);
        }

        .trending-count {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Sprint 5: Notifications Center & Header Bell (WBS 5.x) */
        .nav-bell {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: var(--muted);
            text-decoration: none;
            transition: background-color var(--ease), color var(--ease);
        }

        .nav-bell:hover {
            color: var(--ink);
            background: var(--surface-hover);
        }

        .nav-bell svg {
            width: 20px;
            height: 20px;
        }

        .nav-bell-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #E0245E;
            color: #FFFFFF;
            font-size: 0.68rem;
            font-weight: 700;
            min-width: 17px;
            height: 17px;
            line-height: 17px;
            border-radius: 999px;
            text-align: center;
            padding: 0 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #FFFFFF;
        }

        .nav-col-link .nav-badge {
            margin-left: auto;
            background: var(--accent);
            color: #FFFFFF;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
        }

        .notif-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            transition: background var(--ease);
            position: relative;
        }

        .notif-card:hover {
            background: var(--surface-hover);
        }

        .notif-card.is-unread {
            background: rgba(43, 63, 224, 0.04);
            border-left: 3px solid var(--accent);
        }

        .notif-type-icon {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notif-badge-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--surface);
        }

        .notif-badge-icon svg {
            width: 17px;
            height: 17px;
        }

        .notif-content-wrap {
            flex: 1;
        }

        .notif-user-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .notif-body-text {
            flex: 1;
        }

        .notif-target-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .notif-msg {
            font-size: 0.92rem;
            color: var(--ink);
            line-height: 1.4;
            margin-bottom: 3px;
        }

        .notif-time {
            font-size: 0.78rem;
            color: var(--muted);
        }

        .btn-notif-mark {
            background: none;
            border: none;
            color: var(--accent);
            font-size: 1.4rem;
            line-height: 1;
            padding: 4px;
            cursor: pointer;
        }

        .action-stat {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 500;
            padding: 6px 8px;
        }

        .action-stat svg {
            width: 19px;
            height: 19px;
            stroke-width: 2.05;
        }

        .inline-form {
            display: inline;
            margin: 0;
        }

        .edit-form {
            margin-top: 10px;
        }

        .edit-form-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        /* ---------- Compose: inline at the top of the feed ---------- */
        .compose-card {
            padding: 12px 16px 10px;
            border-bottom: 1px solid var(--line);
        }

        .narrow-wrap .compose-card {
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .compose-flex {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .compose-card textarea.form-control {
            border: none;
            box-shadow: none;
            background: transparent;
            padding: 9px 0 2px;
            font-size: 1.1rem;
            resize: none;
            height: 58px;
        }

        .compose-card textarea.form-control:focus {
            border: none;
            box-shadow: none;
        }

        .compose-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--line);
            margin-top: 8px;
            padding-top: 9px;
        }

        /* ---------- Pager ---------- */
        .pager {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
        }

        .pager a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .pager a:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .pager-info {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 500;
        }

        /* ---------- Empty states: plain words, no dashed box ---------- */
        .empty-state {
            padding: 3.5rem 2rem;
            text-align: center;
        }

        .empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--faint);
            margin-bottom: 0.9rem;
        }

        .empty-icon svg {
            width: 30px;
            height: 30px;
        }

        .empty-title {
            font-size: 1.08rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .empty-text {
            font-size: 0.92rem;
            color: var(--muted);
            max-width: 340px;
            margin: 0 auto;
            line-height: 1.55;
        }

        .empty-text a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .empty-text a:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* ---------- Search / list surfaces ---------- */
        .search-card {
            padding: 4px 16px 14px;
            border-bottom: 1px solid var(--line);
            display: flex;
            gap: 8px;
        }

        .list-card {
            padding: 4px 0;
        }

        .result-note {
            font-size: 0.92rem;
            color: var(--muted);
            padding: 2px 16px 10px;
        }

        /* ---------- Profile ---------- */
        .profile-card {
            padding: 0 0 16px;
            border-bottom: 1px solid var(--line);
        }

        /* Cover band — the brand signature's second (and only other)
           appearance. Gives the profile a presence the flat header
           lacked; the avatar overlaps it, as in real products. */
        .profile-cover {
            height: 118px;
            background: var(--brand-grad);
            position: relative;
            overflow: hidden;
        }

        .profile-cover::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: var(--brand-dots);
            background-size: 20px 20px;
            pointer-events: none;
        }

        /* Cover style variants — Edit Profile stores one of these names
           on the user; the same classes paint the picker swatches. */
        .profile-cover.cover-signature, .cover-swatch.cover-signature { background: var(--brand-grad); }
        .profile-cover.cover-cobalt, .cover-swatch.cover-cobalt { background: var(--cover-cobalt); }
        .profile-cover.cover-teal, .cover-swatch.cover-teal { background: var(--cover-teal); }
        .profile-cover.cover-sunset, .cover-swatch.cover-sunset { background: var(--cover-sunset); }
        .profile-cover.cover-violet, .cover-swatch.cover-violet { background: var(--cover-violet); }
        .profile-cover.cover-graphite, .cover-swatch.cover-graphite { background: var(--cover-graphite); }

        /* Cover style picker (Edit Profile modal) */
        .cover-picker {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .cover-swatch {
            position: relative;
            height: 48px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            outline: 2px solid transparent;
            outline-offset: 2px;
            transition: outline-color var(--ease);
        }

        .cover-swatch::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: var(--brand-dots);
            background-size: 14px 14px;
            pointer-events: none;
        }

        .cover-swatch input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .cover-swatch-name {
            position: absolute;
            left: 8px;
            bottom: 6px;
            z-index: 1;
            color: #FFFFFF;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            pointer-events: none;
        }

        .cover-swatch:has(> input:checked) {
            outline-color: var(--accent);
        }

        .cover-swatch:focus-within {
            outline-color: var(--accent);
        }

        .profile-head {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            padding: 0 16px;
            margin-top: -36px;
            position: relative;
        }

        .profile-head .avatar-lg {
            box-shadow: 0 0 0 3px #FFFFFF;
        }

        .profile-head .profile-action {
            margin-left: auto;
            padding-top: 8px;
        }

        .profile-main {
            order: 3;
            flex: 1 1 100%;
            min-width: 220px;
        }

        .profile-name {
            font-weight: 800;
            color: var(--ink);
            font-size: 1.3rem;
            letter-spacing: -0.01em;
            line-height: 1.25;
        }

        .profile-email {
            font-size: 0.92rem;
            color: var(--muted);
        }

        .profile-bio {
            font-size: 0.97rem;
            color: var(--ink);
            line-height: 1.5;
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .profile-stats {
            display: flex;
            gap: 20px;
            margin-top: 12px;
            font-size: 0.92rem;
            align-items: baseline;
            color: var(--muted);
        }

        .stat-btn {
            background: none;
            border: none;
            padding: 0;
            font-size: 0.92rem;
            color: var(--muted);
        }

        .stat-btn strong, .profile-stats strong {
            color: var(--ink);
            font-weight: 700;
        }

        .stat-btn:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* ---------- Post detail + replies ---------- */
        .detail-card {
            padding: 10px 16px 16px;
        }

        .detail-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .detail-author {
            font-weight: 700;
            color: var(--ink);
            font-size: 0.97rem;
        }

        .detail-meta {
            font-size: 0.9rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        .detail-content {
            font-size: 1.1rem;
            color: var(--ink);
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .detail-foot {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            font-size: 0.9rem;
            color: var(--muted);
        }

        .detail-actions {
            margin-top: 4px;
            display: flex;
            gap: 2px;
            align-items: center;
        }

        .reply-count-note {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 600;
        }

        .reply-card {
            display: flex;
            gap: 11px;
            padding: 13px 16px 11px;
            border-bottom: 1px solid var(--line);
            transition: background-color var(--ease);
        }

        .reply-card:hover {
            background-color: var(--wash);
        }

        .reply-body {
            flex: 1;
            min-width: 0;
        }

        .reply-head {
            display: flex;
            align-items: baseline;
            gap: 5px;
            min-width: 0;
        }

        .reply-author {
            font-weight: 700;
            color: var(--ink);
            font-size: 0.95rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .reply-author:hover {
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .reply-time {
            font-size: 0.87rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .reply-content {
            font-size: 0.95rem;
            color: var(--ink);
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-top: 1px;
        }

        .login-to-reply {
            font-size: 0.92rem;
            color: var(--muted);
            padding: 4px 16px 12px;
        }

        .login-to-reply a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        /* ---------- Modals ---------- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(83, 100, 113, 0.45);
            z-index: 100;
            align-items: flex-start;
            justify-content: center;
            padding: 8vh 1rem 1rem;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            max-height: 72vh;
            overflow-y: auto;
            padding: 1.15rem 1.25rem 1.3rem;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.7);
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.4rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid var(--line);
        }

        .modal-head h3 {
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.3rem;
            line-height: 1;
            color: var(--muted);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--wash);
            color: var(--ink);
        }

        /* ---------- Public hero (logged out): brand, kept quiet ---------- */
        .hero {
            max-width: 880px;
            margin: 0 auto;
            padding: 4.5rem 1.5rem 2.5rem;
            text-align: left;
        }

        .hero-title {
            font-size: 2.9rem;
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -0.03em;
            margin-bottom: 1.1rem;
        }

        .hero-sub {
            font-size: 1.08rem;
            color: var(--muted);
            max-width: 560px;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .hero-actions {
            display: flex;
            justify-content: flex-start;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: var(--accent);
            color: #FFFFFF;
            padding: 12px 26px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 999px;
            text-decoration: none;
            transition: background-color var(--ease);
        }

        .btn-hero-primary:hover {
            background: var(--accent-hover);
        }

        .btn-hero-ghost {
            background: #FFFFFF;
            color: var(--ink);
            padding: 12px 26px;
            font-size: 1rem;
            font-weight: 700;
            border: 1px solid var(--border-strong);
            border-radius: 999px;
            text-decoration: none;
            transition: background-color var(--ease);
        }

        .btn-hero-ghost:hover {
            background: var(--wash);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 2rem;
            margin-top: 3.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--line);
            text-align: left;
        }

        .feature-card {
            padding: 0;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            margin-bottom: 10px;
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
        }

        .feature-card h4 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .feature-card p {
            font-size: 0.92rem;
            color: var(--muted);
            line-height: 1.55;
        }

        /* ---------- Footer ---------- */
        .site-footer {
            background: var(--bg-base);
            border-top: 1px solid var(--line);
            color: var(--muted);
            padding: 1.25rem 2rem;
            text-align: center;
            font-size: 0.83rem;
            margin-top: auto;
        }

        .site-footer strong {
            color: var(--ink);
            font-weight: 700;
        }

        /* ---------- Responsive ---------- */
        /* Sprint 5 UI polish (M-19): with the right rail hidden the
           main column grows to fill the freed space (600px -> 680px)
           instead of leaving dead white space beside the feed. */
        @media (max-width: 1080px) {
            .layout-3col {
                grid-template-columns: 76px minmax(0, 680px);
            }
            .col-side {
                display: none;
            }
            .col-nav {
                padding: 10px 12px;
                align-items: center;
            }
            .nav-col-link {
                padding: 12px;
                align-self: center;
            }
            .nav-col-link span {
                display: none;
            }
            .nav-col-link svg {
                width: 26px;
                height: 26px;
            }
        }

        @media (max-width: 720px) {
            .layout-3col {
                grid-template-columns: 1fr;
            }
            .col-nav {
                flex-direction: row;
                justify-content: space-around;
                position: sticky;
                top: 52px;
                z-index: 40;
                background: rgba(0, 0, 0, 0.85);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border-bottom: 1px solid var(--line);
                padding: 4px 8px;
            }
            .nav-col-active {
                color: var(--accent);
            }
            .col-main {
                border-left: none;
                border-right: none;
                min-height: 0;
            }
            .col-head {
                top: 99px;
            }
            .narrow-wrap {
                border-left: none;
                border-right: none;
            }
            .nav-inner {
                padding: 6px 12px;
            }
            .user-tag {
                display: none;
            }
            .brand-text {
                font-size: 1.1rem;
            }
            .hero {
                padding-top: 2.5rem;
            }
            .hero-title {
                font-size: 2.1rem;
            }
            .avatar-lg {
                width: 64px;
                height: 64px;
                font-size: 1.6rem;
            }
            .auth-shell {
                grid-template-columns: 1fr;
                margin: 1.25rem auto 2rem;
            }
            .auth-brand {
                border-radius: 16px 16px 0 0;
                padding: 1.15rem 1.4rem 1.3rem;
                gap: 0.9rem;
            }
            .auth-tagline {
                font-size: 1.25rem;
                margin-bottom: 0;
            }
            .auth-points {
                display: none;
            }
            .auth-shell .auth-card {
                border-radius: 0 0 16px 16px;
                border-left: 1px solid var(--line);
                border-top: none;
                padding: 1.5rem 1.25rem;
            }
            .profile-cover {
                height: 92px;
            }
        }

        /* Responsive polish for compact mobile (down to 360px) */
        @media (max-width: 480px) {
            .nav-inner {
                padding: 6px 10px;
                gap: 8px;
            }
            .brand-link {
                gap: 8px;
            }
            .brand-text {
                font-size: 1.05rem;
            }
            .btn-nav-primary {
                padding: 6px 12px;
                font-size: 0.86rem;
            }
            .nav-link {
                padding: 6px 8px;
                font-size: 0.88rem;
            }
            .post-card {
                padding: 11px 12px;
                gap: 10px;
            }
            .avatar-md {
                width: 36px;
                height: 36px;
                font-size: 0.95rem;
            }
            .post-head {
                flex-wrap: wrap;
                row-gap: 2px;
            }
            .post-author {
                max-width: 170px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .post-card-actions {
                flex-wrap: wrap;
                gap: 4px;
            }
            .compose-card {
                padding: 10px 12px;
            }
            .compose-card textarea.form-control {
                font-size: 1rem;
            }
            .detail-card {
                padding: 10px 12px 14px;
            }
            .reply-card {
                padding: 11px 12px 10px;
                gap: 9px;
            }
            .profile-card {
                padding-bottom: 12px;
            }
            .profile-head {
                padding: 0 12px;
            }
            .profile-stats {
                gap: 12px;
                font-size: 0.86rem;
                flex-wrap: wrap;
            }
            .cover-picker {
                grid-template-columns: repeat(2, 1fr);
            }
            .modal-box {
                padding: 1rem;
                max-width: 95%;
            }
            .auth-shell .auth-card {
                padding: 1.25rem 1rem;
            }
            .search-card {
                padding: 10px 12px;
            }
            .hero-title {
                font-size: 1.85rem;
            }
            .hero-sub {
                font-size: 0.98rem;
            }
        }

        @media (max-width: 360px) {
            .nav-inner {
                padding: 5px 8px;
            }
            .brand-mark {
                width: 26px;
                height: 26px;
            }
            .brand-text {
                font-size: 0.96rem;
            }
            .btn-nav-primary {
                padding: 5px 9px;
                font-size: 0.8rem;
            }
            .nav-link {
                padding: 5px 6px;
                font-size: 0.82rem;
            }
            .post-author {
                max-width: 130px;
            }
            .col-head, .page-title {
                font-size: 1.15rem;
                padding: 10px 12px;
            }
            .btn-primary, .btn-outline, .btn-cancel {
                padding: 6px 14px;
                font-size: 0.88rem;
            }
            .stat-btn, .profile-stats strong {
                font-size: 0.82rem;
            }
            .action-reply, .action-like, .btn-bookmark {
                font-size: 0.78rem;
                padding: 4px 6px;
            }
            .action-reply .action-ic, .action-like .action-ic {
                width: 28px;
                height: 28px;
            }
            .col-nav {
                padding: 2px 4px;
            }
            .nav-col-link {
                padding: 8px 6px;
            }
        }

        /* ---------- Micro-interactions & Tactile Polish ---------- */
        .btn-primary, .btn-outline, .btn-cancel, .btn-danger-outline,
        .btn-nav-primary, .btn-hero-primary, .btn-hero-ghost, .btn-follow {
            transition: background-color var(--ease), border-color var(--ease), color var(--ease), transform 120ms ease, box-shadow var(--ease);
        }

        .btn-primary:active,
        .btn-outline:active,
        .btn-nav-primary:active,
        .btn-follow:active,
        .btn-hero-primary:active,
        .btn-hero-ghost:active,
        .btn-danger-outline:active {
            transform: scale(0.97);
        }

        .btn-bookmark:active {
            transform: scale(0.95);
        }

        @keyframes heartPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        .action-like.is-liked svg {
            animation: heartPop 220ms ease;
        }

        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        /* Profile Tabs */
        .profile-tabs {
            display: flex;
            gap: 1.5rem;
            border-bottom: 1px solid var(--line);
            margin-bottom: 1.25rem;
            padding: 0 16px;
        }

        .profile-tab {
            padding: 10px 4px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            color: var(--muted);
            border-bottom: 2px solid transparent;
            transition: color var(--ease), border-color var(--ease);
        }

        .profile-tab:hover {
            color: var(--ink);
        }

        .profile-tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        /* Sprint 5 (H3): SQLite-fallback banner — amber strip, impossible
           to miss, because the app is on a different database. */
        .db-fallback-banner {
            background: #FEF3C7;
            color: #92400E;
            border-bottom: 1px solid #F59E0B;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 7px 16px;
        }

        /* ---------- Benchmark page (Sprint 5 UI polish, M-11) ---------- */
        .bench-card {
            padding: 4px 16px 16px;
            border-bottom: 1px solid var(--line);
        }

        .bench-meta {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .bench-meta strong {
            color: var(--ink);
        }

        .bench-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            margin-bottom: 14px;
        }

        .bench-table th, .bench-table td {
            text-align: left;
            padding: 8px 10px 8px 0;
            border-bottom: 1px solid var(--line);
            font-variant-numeric: tabular-nums;
        }

        .bench-table th {
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .bench-table td:first-child {
            font-weight: 700;
        }

        .bench-callout {
            background: var(--success-tint);
            border: 1px solid var(--success-border);
            color: #14532D;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .bench-chart {
            padding: 4px 16px 16px;
            border-bottom: 1px solid var(--line);
        }

        .bench-note {
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.55;
            padding: 12px 16px 0;
        }

    </style>
</head>
<body>

<?php if ($isDbFallback): ?>
    <div class="db-fallback-banner" role="alert">Notice: MySQL is unreachable — running on the local SQLite fallback. Accounts and posts saved here will not appear once MySQL is back.</div>
<?php endif; ?>

<header class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="brand-link">
            <?= brandMarkSvg() ?>
            <div>
                <span class="brand-text">CampusConnect</span>
            </div>
        </a>

        <ul class="nav-menu">
            <li>
                <button type="button" class="btn-theme-toggle" id="theme-toggle-btn" aria-label="Toggle dark/light theme" title="Toggle dark/light theme">
                    <svg class="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <svg class="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                </button>
            </li>
            <?php if($currentUser): ?>
                <?php
                    $unreadNotifCount = 0;
                    try {
                        require_once __DIR__ . '/../classes/Notification.php';
                        $headerDb = isset($db) ? $db : Database::getInstance()->getConnection();
                        $unreadNotifCount = (new Notification($headerDb))->countUnread((int)$currentUser['id']);
                    } catch (Throwable $e) {}
                ?>
                <li>
                    <a href="notifications.php" class="nav-bell" aria-label="Notifications" title="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span class="nav-bell-badge" data-notif-badge style="<?= $unreadNotifCount > 0 ? '' : 'display:none;' ?>"><?= $unreadNotifCount > 99 ? '99+' : $unreadNotifCount ?></span>
                    </a>
                </li>
                <li>
                    <span class="user-tag">
                        <span class="avatar-xs<?= avatarToneClass((int)$currentUser['id'], (string)$currentUser['role']) ?>"><?= htmlspecialchars(strtoupper(mb_substr(trim($currentUser['name']), 0, 1, 'UTF-8'))) ?></span>
                        <?= htmlspecialchars($currentUser['name']) ?> <span class="user-role">(<?= htmlspecialchars(class_exists('Authorization') ? Authorization::displayLabel($currentUser['role'] ?? '') : ($currentUser['role'] ?? '')) ?>)</span>
                    </span>
                </li>
                <?php if (class_exists('Auth') && Auth::can('user.manage')): ?>
                    <li><a href="admin.php" class="nav-link">Admin</a></li>
                <?php endif; ?>
                <li>
                    <!-- Sprint 5: logout is a POST with a CSRF token, like every other state change. -->
                    <form method="POST" action="logout.php" class="inline-form">
                        <?= class_exists('Csrf') ? Csrf::field() : '' ?>
                        <button type="submit" class="nav-link nav-link-logout" style="background: none; border: none; font: inherit; cursor: pointer;">Logout</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link">Log In</a></li>
                <li><a href="signup.php" class="btn-nav-primary">Create Account</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<main class="page-content">