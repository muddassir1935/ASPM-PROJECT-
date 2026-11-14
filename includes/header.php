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
                var urlParams = new URLSearchParams(window.location.search);
                var themeParam = urlParams.get('theme');
                if (themeParam === 'light' || themeParam === 'dark') {
                    try { localStorage.setItem('cc_theme', themeParam); } catch (err) {}
                    document.documentElement.setAttribute('data-theme', themeParam);
                    return;
                }
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
           CampusConnect Dual Design System — The Modern Minimalist
           Monochromatic foundation + Electric Blue / Cyan Glow Accent
           WCAG AAA contrast, eye-strain free dark slate & crisp off-white.
           ============================================================ */
        :root, [data-theme="dark"] {
            /* Dark Theme Tokens: The Modern Minimalist (Deep Slate #0F172A + Cyan #38BDF8) */
            --bg-base: #0F172A;
            --bg-surface: #1E293B;
            --bg-surface-hover: #273549;
            --bg-input: #152033;
            --nav-bg: rgba(15, 23, 42, 0.85);
            --modal-bg: #1E293B;
            --modal-shadow: 0 16px 48px rgba(0, 0, 0, 0.65);

            --ink: #F1F5F9;
            --ink-soft: #E2E8F0;
            --muted: #94A3B8;
            --faint: #64748B;

            --accent: #38BDF8;
            --accent-hover: #7DD3FC;
            --accent-ink: #0F172A;
            --accent-tint: rgba(56, 189, 248, 0.12);
            --accent-ring: rgba(56, 189, 248, 0.35);
            --accent-aura: rgba(56, 189, 248, 0.22);

            --line: #334155;
            --border-strong: #475569;
            --wash: #1E293B;
            --field-bg: #152033;

            --danger: #F43F5E;
            --danger-text: #F43F5E;
            --danger-tint: rgba(244, 63, 94, 0.12);
            --danger-border: rgba(244, 63, 94, 0.3);

            --success: #10B981;
            --success-tint: rgba(16, 185, 129, 0.12);
            --success-border: rgba(16, 185, 129, 0.3);

            --btn-follow-bg: #F1F5F9;
            --btn-follow-color: #0F172A;
            --btn-follow-border: #F1F5F9;
            --btn-follow-hover-bg: #E2E8F0;
            --brand-arc-2: #F1F5F9;

            /* Brand signature — deep obsidian-slate to cyan-blue */
            --brand-grad: linear-gradient(150deg, #090E17 0%, #0F172A 52%, #2563EB 138%);
            --brand-dots: radial-gradient(rgba(255, 255, 255, 0.15) 1.2px, transparent 1.9px);

            /* Cover style variants (profile cover picker) — high-contrast dark slate tones */
            --cover-cobalt: linear-gradient(150deg, #081229 0%, #0F275A 52%, #38BDF8 138%);
            --cover-teal: linear-gradient(150deg, #031D1B 0%, #064E3B 54%, #10B981 140%);
            --cover-sunset: linear-gradient(150deg, #2A1726 0%, #701A4E 54%, #F43F5E 142%);
            --cover-violet: linear-gradient(150deg, #130E2E 0%, #3B1B7D 54%, #818CF8 140%);
            --cover-graphite: linear-gradient(150deg, #070B12 0%, #1E293B 55%, #475569 145%);

            --font: 'Public Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-display: 'Bricolage Grotesque', 'Public Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

            --ease: 140ms ease;
        }

        /* Light Theme Tokens: The Modern Minimalist (Off-white #F8F9FA + Electric Blue #2563EB) */
        [data-theme="light"] {
            --bg-base: #F8F9FA;
            --bg-surface: #FFFFFF;
            --bg-surface-hover: #F1F5F9;
            --bg-input: #EFF2F5;
            --nav-bg: rgba(248, 249, 250, 0.88);
            --modal-bg: #FFFFFF;
            --modal-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);

            --ink: #1A1D20;
            --ink-soft: #2D3339;
            --muted: #5A6065;
            --faint: #7E878E;

            --accent: #2563EB;
            --accent-hover: #1D4ED8;
            --accent-ink: #FFFFFF;
            --accent-tint: rgba(37, 99, 235, 0.10);
            --accent-ring: rgba(37, 99, 235, 0.25);
            --accent-aura: rgba(37, 99, 235, 0.14);

            --line: #E2E8F0;
            --border-strong: #CBD5E1;
            --wash: #F8F9FA;
            --field-bg: #EFF2F5;

            --danger: #DC2626;
            --danger-text: #DC2626;
            --danger-tint: #FEF2F2;
            --danger-border: #FECACA;

            --success: #059669;
            --success-tint: #ECFDF5;
            --success-border: #A7F3D0;

            --btn-follow-bg: #1A1D20;
            --btn-follow-color: #FFFFFF;
            --btn-follow-border: #1A1D20;
            --btn-follow-hover-bg: #2D3339;
            --brand-arc-2: #1A1D20;

            --brand-grad: linear-gradient(150deg, #1A1D20 0%, #1E3A8A 52%, #2563EB 138%);
            --brand-dots: radial-gradient(rgba(255, 255, 255, 0.13) 1.2px, transparent 1.9px);

            --cover-cobalt: linear-gradient(150deg, #0A1A4A 0%, #172B7A 52%, #2563EB 138%);
            --cover-teal: linear-gradient(150deg, #03211F 0%, #0A5C50 54%, #059669 140%);
            --cover-sunset: linear-gradient(150deg, #33203F 0%, #8A4A63 54%, #DC2626 142%);
            --cover-violet: linear-gradient(150deg, #181344 0%, #46309C 54%, #6366F1 140%);
            --cover-graphite: linear-gradient(150deg, #1A1D20 0%, #334155 55%, #64748B 145%);
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
            color: var(--accent-ink, #FFFFFF);
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
            max-width: 1480px;
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
            max-width: 1480px;
            width: 100%;
            margin: 0 auto;
        }

        .narrow-wrap {
            max-width: 760px;
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

        /* Sticky Feed Tabs Container (X-style) */
        .feed-header-sticky {
            position: sticky;
            top: 52px;
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            z-index: 30;
            display: flex;
        }

        .feed-tabs {
            display: flex;
            width: 100%;
        }

        .feed-tab {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 53px;
            position: relative;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background-color var(--ease), color var(--ease);
        }

        .feed-tab:hover {
            background-color: var(--wash);
            color: var(--ink);
        }

        .feed-tab.active {
            color: var(--ink);
            font-weight: 700;
        }

        .feed-tab .feed-tab-label {
            position: relative;
            display: inline-flex;
            align-items: center;
            height: 100%;
            padding: 0 4px;
        }

        .feed-tab.active .feed-tab-label::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
            border-radius: 9999px;
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
            color: var(--accent-ink, #FFFFFF);
            border: 1px solid var(--accent);
            padding: 7px 20px;
            border-radius: 9999px;
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
            padding: 10px 14px;
            font-size: 0.97rem;
            border: 1px solid var(--line);
            border-radius: 10px;
            background-color: var(--field-bg);
            color: var(--ink);
            transition: border-color var(--ease), box-shadow var(--ease), background-color var(--ease);
            outline: none;
        }

        .form-control:focus {
            background-color: var(--bg-surface);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.18);
        }

        .password-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .password-input-wrap .form-control {
            padding-right: 44px;
        }

        .btn-toggle-pw {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            padding: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            transition: color var(--ease), background var(--ease);
        }

        .btn-toggle-pw:hover {
            color: var(--ink);
            background: var(--wash);
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
            grid-template-columns: 260px minmax(0, 1fr) 350px;
            justify-content: center;
            align-items: start;
        }

        .col-nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
            position: sticky;
            top: 52px;
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

        .nav-user-dock {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
            width: 100%;
            position: relative;
        }

        .nav-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 9999px;
            text-decoration: none;
            color: var(--ink);
            transition: background-color var(--ease), box-shadow var(--ease);
            width: 100%;
            background: transparent;
            border: none;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
        }

        .nav-user-pill:hover,
        .nav-user-pill[aria-expanded="true"] {
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

        /* Nav User Dropdown Menu (X/Twitter style popover above the dock) */
        .nav-user-dropdown {
            position: absolute;
            bottom: calc(100% + 10px);
            left: 0;
            width: 270px;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
            padding: 8px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 2px;
            animation: navUserDropdownFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }

        [data-theme="light"] .nav-user-dropdown {
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.15);
        }

        @keyframes navUserDropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-user-dropdown-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px 12px;
            border-bottom: 1px solid var(--line);
            margin-bottom: 4px;
        }

        .nav-user-dropdown-header .nav-user-name {
            font-size: 0.96rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
        }

        .nav-user-dropdown-header .nav-user-handle {
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.2;
        }

        .nav-user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--ink);
            font-size: 0.92rem;
            font-weight: 600;
            transition: background-color var(--ease), color var(--ease);
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            box-sizing: border-box;
        }

        .nav-user-dropdown-item:hover {
            background: var(--bg-surface-hover);
            color: var(--accent);
        }

        .nav-user-dropdown-item svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .nav-user-dropdown-divider {
            height: 1px;
            background: var(--line);
            margin: 4px 0;
        }

        .nav-user-dropdown-item.item-danger {
            color: var(--danger-text);
        }

        .nav-user-dropdown-item.item-danger:hover {
            background: var(--danger-tint);
            color: var(--danger-text);
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

        /* ---------- Admin Panel UI ---------- */
        .admin-search-wrap {
            display: flex;
            align-items: center;
            background: var(--field-bg);
            border: 1px solid var(--line);
            border-radius: 9999px;
            padding: 4px 6px 4px 14px;
            gap: 8px;
            transition: border-color var(--ease), background var(--ease);
        }

        .admin-search-wrap:focus-within {
            border-color: var(--accent);
            background: var(--bg-surface);
        }

        .admin-search-wrap .search-ic {
            color: var(--muted);
            flex-shrink: 0;
        }

        .admin-search-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--ink);
            font-size: 0.94rem;
        }

        .admin-search-btn {
            background: var(--accent);
            color: var(--accent-ink, #FFFFFF);
            border: none;
            border-radius: 9999px;
            padding: 6px 16px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background var(--ease);
        }

        .admin-search-btn:hover {
            background: var(--accent-hover);
        }

        .btn-clear-search {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0 8px;
        }

        .btn-clear-search:hover {
            color: var(--ink);
        }

        .admin-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .admin-bench-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 9999px;
            text-decoration: none;
            color: var(--ink);
            border: 1px solid var(--line);
            background: var(--field-bg);
            transition: all var(--ease);
        }

        .admin-bench-pill:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--bg-surface-hover);
            transform: translateY(-1px);
        }

        /* ---------- Explore Search Header ---------- */
        .explore-header-wrap {
            position: sticky;
            top: 52px;
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            padding: 10px 16px;
            z-index: 25;
        }

        .explore-search-bar {
            display: flex;
            align-items: center;
            background: var(--field-bg);
            border: 1px solid var(--line);
            border-radius: 9999px;
            padding: 5px 8px 5px 16px;
            gap: 10px;
            transition: border-color var(--ease), background var(--ease), box-shadow var(--ease);
        }

        .explore-search-bar:focus-within {
            border-color: var(--accent);
            background: var(--bg-surface);
            box-shadow: 0 0 0 1px var(--accent);
        }

        .explore-search-bar .search-ic {
            color: var(--muted);
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            transition: color var(--ease);
        }

        .explore-search-bar:focus-within .search-ic {
            color: var(--accent);
        }

        .explore-search-input {
            flex: 1;
            min-width: 0;
            background: transparent;
            border: none;
            outline: none;
            color: var(--ink);
            font-size: 0.95rem;
        }

        .explore-search-input::placeholder {
            color: var(--muted);
        }

        .explore-search-clear {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--hover-wash);
            color: var(--muted);
            text-decoration: none;
            flex-shrink: 0;
            transition: background var(--ease), color var(--ease);
        }

        .explore-search-clear:hover {
            background: var(--line);
            color: var(--ink);
        }

        .explore-search-btn {
            background: var(--accent);
            color: var(--accent-ink, #FFFFFF);
            border: none;
            border-radius: 9999px;
            padding: 6px 16px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            flex-shrink: 0;
            transition: background var(--ease);
        }

        .explore-search-btn:hover {
            background: var(--accent-hover);
        }

        .explore-filter-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--line);
            font-size: 0.92rem;
        }

        .explore-filter-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
        }

        .explore-filter-badge strong {
            color: var(--ink);
            font-weight: 700;
        }

        .explore-filter-clear {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .explore-filter-clear:hover {
            text-decoration: underline;
        }

        .admin-user-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            gap: 16px;
            flex-wrap: wrap;
            transition: background-color var(--ease);
        }

        .admin-user-card:hover {
            background-color: var(--wash);
        }

        .admin-user-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 240px;
            flex: 1;
        }

        .admin-user-avatar {
            text-decoration: none;
            flex-shrink: 0;
        }

        .admin-user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .admin-user-name-line {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-user-name {
            font-weight: 700;
            color: var(--ink);
            text-decoration: none;
            font-size: 0.98rem;
        }

        .admin-user-name:hover {
            text-decoration: underline;
        }

        .admin-user-meta {
            font-size: 0.84rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .admin-user-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-self-pill {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--muted);
            padding: 5px 12px;
            border-radius: 9999px;
            background: var(--wash);
            border: 1px solid var(--line);
        }

        .admin-role-form {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        .admin-role-select {
            background: var(--field-bg);
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
        }

        .admin-role-select:focus {
            border-color: var(--accent);
        }

        .admin-btn-action {
            background: var(--wash);
            color: var(--ink);
            border: 1px solid var(--line);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--ease);
        }

        .admin-btn-action:hover {
            background: var(--accent);
            color: #FFFFFF;
            border-color: var(--accent);
        }

        .admin-btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--wash);
            color: var(--muted);
            border: 1px solid var(--line);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--ease);
        }

        .admin-btn-reset:hover {
            color: var(--ink);
            border-color: var(--muted);
            background: var(--bg-surface);
        }

        .admin-mod-delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(220, 38, 38, 0.1);
            color: #EF4444;
            border: 1px solid rgba(220, 38, 38, 0.25);
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--ease);
        }

        .admin-mod-delete-btn:hover {
            background: #EF4444;
            color: #FFFFFF;
            border-color: #EF4444;
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
            padding: 14px 16px 12px;
            border-bottom: 1px solid var(--line);
            background: var(--bg-surface);
            transition: background-color var(--ease);
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
            padding: 8px 0 4px;
            font-size: 1.15rem;
            line-height: 1.45;
            color: var(--ink);
            resize: none;
            height: 58px;
            width: 100%;
        }

        .compose-card textarea.form-control::placeholder {
            color: var(--muted);
            font-size: 1.15rem;
        }

        .compose-card textarea.form-control:focus {
            border: none;
            box-shadow: none;
            outline: none;
        }

        .compose-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--line);
            margin-top: 10px;
            padding-top: 10px;
        }

        .compose-left-tools {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .compose-right-tools {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .announcement-chip {
            cursor: pointer;
            user-select: none;
            display: inline-flex;
            align-items: center;
        }

        .announcement-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }

        .announcement-chip .chip-content {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--muted);
            padding: 5px 12px;
            border-radius: 9999px;
            border: 1px solid var(--line);
            background: transparent;
            transition: all var(--ease);
        }

        .announcement-chip:hover .chip-content {
            background: var(--wash);
            color: var(--ink);
            border-color: var(--muted);
        }

        .announcement-chip input:checked + .chip-content {
            background: rgba(29, 155, 240, 0.15);
            color: var(--accent);
            border-color: var(--accent);
            font-weight: 700;
        }

        .char-count-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 150ms ease;
        }

        .char-count-wrap.has-content {
            opacity: 1;
            pointer-events: auto;
        }

        .char-count-ring {
            display: block;
            transform: rotate(-90deg);
        }

        .char-counter-text {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--muted);
            min-width: 24px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .compose-btn-divider {
            width: 1px;
            height: 24px;
            background: var(--line);
            opacity: 0;
            transition: opacity 150ms ease;
        }

        .compose-btn-divider.has-content {
            opacity: 1;
        }

        .compose-btn {
            background: var(--accent) !important;
            color: var(--accent-ink, #FFFFFF) !important;
            border: none !important;
            font-weight: 700 !important;
            font-size: 0.92rem !important;
            padding: 8px 18px !important;
            border-radius: 9999px !important;
            cursor: pointer;
            box-shadow: none !important;
            transition: opacity 150ms ease, background-color 150ms ease !important;
        }

        .compose-btn:disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }

        .compose-btn:not(:disabled):hover {
            background: var(--accent-hover) !important;
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
        .profile-sticky-bar {
            position: sticky;
            top: 52px;
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 6px 16px;
            height: 53px;
        }

        .profile-back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 9999px;
            color: var(--ink);
            text-decoration: none;
            transition: background-color var(--ease);
        }

        .profile-back-btn:hover {
            background-color: var(--wash);
        }

        .profile-sticky-meta {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .profile-sticky-name {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--ink);
            margin: 0;
            letter-spacing: -0.01em;
        }

        .profile-sticky-sub {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.1;
        }

        .profile-card {
            padding: 0 0 16px;
            border-bottom: 1px solid var(--line);
        }

        /* Cover band */
        .profile-cover {
            height: 180px;
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
            opacity: 0.25;
            pointer-events: none;
        }

        /* Cover style variants */
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

        .profile-hero-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 0 16px;
            position: relative;
        }

        .profile-hero-row .avatar-lg {
            width: 120px;
            height: 120px;
            font-size: 2.8rem;
            box-shadow: 0 0 0 4px var(--bg);
            border-radius: 9999px;
            margin-top: -60px;
            position: relative;
            z-index: 2;
        }

        .profile-hero-row .profile-action {
            padding-top: 12px;
        }

        .profile-edit-btn {
            font-size: 0.92rem;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 9999px;
            color: var(--ink);
            border: 1px solid var(--line);
            background: transparent;
            cursor: pointer;
            transition: background-color var(--ease), border-color var(--ease);
        }

        .profile-edit-btn:hover {
            background-color: var(--wash);
            border-color: var(--muted);
        }

        .profile-details {
            padding: 14px 16px 4px;
        }

        .profile-name-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .profile-display-name {
            font-weight: 800;
            color: var(--ink);
            font-size: 1.35rem;
            letter-spacing: -0.01em;
            line-height: 1.2;
            margin: 0;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 9999px;
            background: var(--wash);
            color: var(--muted);
            border: 1px solid var(--line);
            vertical-align: middle;
            line-height: 1.4;
            transition: color var(--ease), border-color var(--ease);
        }

        .role-badge svg {
            width: 12px;
            height: 12px;
            flex-shrink: 0;
            color: var(--accent);
        }

        .role-badge:hover {
            color: var(--ink);
            border-color: var(--muted);
        }

        .role-badge-system_admin,
        .role-badge-society_admin,
        .role-badge-student {
            background: var(--wash);
            color: var(--muted);
            border: 1px solid var(--line);
        }

        .profile-handle {
            font-size: 0.94rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .profile-bio {
            font-size: 0.98rem;
            color: var(--ink);
            line-height: 1.5;
            margin-top: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .profile-metadata-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 12px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .profile-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .profile-stats-row {
            display: flex;
            gap: 20px;
            margin-top: 14px;
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
            cursor: pointer;
            display: inline-flex;
            gap: 5px;
        }

        .stat-btn strong, .profile-stats-row strong {
            color: var(--ink);
            font-weight: 700;
        }

        .stat-btn:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* Profile Tabs (X-Style) */
        .profile-tabs-header {
            display: flex;
            width: 100%;
            border-bottom: 1px solid var(--line);
        }

        .profile-tab {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 52px;
            position: relative;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background-color var(--ease), color var(--ease);
        }

        .profile-tab:hover {
            background-color: var(--wash);
            color: var(--ink);
        }

        .profile-tab.active {
            color: var(--ink);
            font-weight: 700;
        }

        .profile-tab .profile-tab-label {
            position: relative;
            display: inline-flex;
            align-items: center;
            height: 100%;
            padding: 0 4px;
        }

        .profile-tab.active .profile-tab-label::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
            border-radius: 9999px;
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

        /* ---------- Public Landing View (The Iconic X / Twitter Standard) ---------- */
        .x-landing-shell {
            max-width: 1480px;
            margin: 0 auto;
            min-height: calc(100vh - 53px - 60px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem 2.5rem;
            gap: 4rem;
        }

        .x-landing-left {
            flex: 1.15;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
        }

        /* Architectural Specular Brand Showcase (Interactive Cursor Glow) */
        .x-brand-architect {
            position: relative;
            width: clamp(280px, 34vw, 440px);
            height: clamp(280px, 34vw, 440px);
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            transition: transform 0.35s cubic-bezier(0.2, 0, 0, 1);
        }

        .x-brand-architect:hover {
            transform: scale(1.03);
        }

        .x-giant-logo {
            width: 100%;
            height: 100%;
            display: block;
            overflow: visible;
        }

        /* Base Solid Crisp Arcs (100% Brand Punch) */
        .x-logo-base .arch-arc-1 {
            stroke: var(--accent);
            filter: drop-shadow(0 14px 32px rgba(37, 99, 235, 0.24));
        }

        .x-logo-base .arch-arc-2 {
            stroke: var(--ink);
            filter: drop-shadow(0 10px 24px rgba(0, 0, 0, 0.15));
        }

        /* Specular Gloss Overlay (Follows Cursor) */
        .x-logo-shine-layer {
            position: absolute;
            inset: 0;
            pointer-events: none;
            mask-image: radial-gradient(circle 160px at var(--mx, 50%) var(--my, 50%), black 20%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle 160px at var(--mx, 50%) var(--my, 50%), black 20%, transparent 80%);
            transition: opacity 0.25s ease;
            opacity: var(--shine-opacity, 0.85);
            filter: drop-shadow(0 0 16px var(--accent));
        }

        /* Idle ambient sheen sweeping smoothly along curves */
        .x-brand-architect.is-idle .x-logo-shine-layer {
            animation: idleBeamSweep 6s ease-in-out infinite alternate;
        }

        @keyframes idleBeamSweep {
            0% {
                mask-position: 20% 25%;
                -webkit-mask-position: 20% 25%;
                opacity: 0.50;
            }
            50% {
                mask-position: 80% 75%;
                -webkit-mask-position: 80% 75%;
                opacity: 0.95;
            }
            100% {
                mask-position: 30% 80%;
                -webkit-mask-position: 30% 80%;
                opacity: 0.55;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .x-brand-architect.is-idle .x-logo-shine-layer {
                animation: none !important;
            }
        }

        .x-landing-right {
            flex: 1;
            max-width: 540px;
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .x-hero-headline {
            font-size: clamp(2.6rem, 5vw, 4.2rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: var(--ink);
            margin: 0 0 2rem 0;
            font-family: var(--font-display);
        }

        .x-hero-subhead {
            font-size: clamp(1.4rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin: 0 0 1.8rem 0;
            font-family: var(--font-display);
        }

        .x-auth-actions {
            width: 100%;
            max-width: 320px;
            display: flex;
            flex-direction: column;
        }

        .x-btn-signup {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 44px;
            background: var(--accent);
            color: var(--accent-ink, #FFFFFF);
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 14px var(--accent-ring);
            transition: all var(--ease);
        }

        .x-btn-signup:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--accent-ring);
        }

        .x-disclaimer {
            font-size: 0.72rem;
            line-height: 1.4;
            color: var(--muted);
            margin: 10px 0 2rem 0;
        }

        .x-disclaimer a {
            color: var(--accent);
            text-decoration: none;
        }

        .x-disclaimer a:hover {
            text-decoration: underline;
        }

        .x-signin-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 0.5rem;
        }

        .x-signin-prompt {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
            margin: 0;
        }

        .x-btn-signin {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 44px;
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--border-strong);
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: all var(--ease);
        }

        .x-btn-signin:hover {
            background: var(--accent-tint);
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .x-trending-strip {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--line);
            max-width: 440px;
        }

        .x-trending-title {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .x-trending-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .x-tag-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 9999px;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            color: var(--ink);
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--ease);
        }

        .x-tag-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--bg-surface-hover);
        }

        .x-tag-hash {
            color: var(--accent);
            font-weight: 700;
        }

        .x-tag-meta {
            font-size: 0.72rem;
            color: var(--muted);
            margin-left: 2px;
        }

        @media (max-width: 900px) {
            .x-landing-shell {
                flex-direction: column;
                align-items: flex-start;
                padding: 2.5rem 1.5rem;
                gap: 2rem;
                min-height: auto;
            }

            .x-landing-left {
                justify-content: flex-start;
                padding: 0;
            }

            .x-brand-architect {
                width: 58px;
                height: 58px;
                margin-bottom: 0.5rem;
            }

            .x-logo-shine-layer {
                mask-image: none !important;
                -webkit-mask-image: none !important;
                opacity: 0.9;
            }

            .x-hero-headline {
                font-size: 2.5rem;
                margin-bottom: 1.25rem;
            }

            .x-hero-subhead {
                font-size: 1.35rem;
                margin-bottom: 1.5rem;
            }

            .x-auth-actions {
                max-width: 100%;
            }

            .x-trending-strip {
                max-width: 100%;
            }
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
           main column grows to fill the freed space
           instead of leaving dead white space beside the feed. */
        @media (max-width: 1080px) {
            .layout-3col {
                grid-template-columns: 76px minmax(0, 1fr);
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
            .nav-user-details,
            .nav-user-dots {
                display: none;
            }
            .nav-user-pill {
                justify-content: center;
                padding: 8px;
                width: 44px;
                height: 44px;
                margin: 0 auto;
            }
            .nav-user-dropdown {
                left: 0;
                width: 260px;
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
                background: var(--nav-bg);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border-bottom: 1px solid var(--line);
                padding: 4px 8px;
            }
            .nav-user-dock {
                margin-top: 0;
                padding-top: 0;
                border-top: none;
                width: auto;
            }
            .nav-user-details,
            .nav-user-dots {
                display: none;
            }
            .nav-user-pill {
                padding: 6px;
                width: auto;
                height: auto;
            }
            .nav-user-dropdown {
                top: calc(100% + 8px);
                bottom: auto;
                right: 8px;
                left: auto;
                width: 260px;
            }
            .nav-col-active {
                color: var(--accent);
            }
            .col-main {
                border-left: none;
                border-right: none;
                min-height: 0;
            }
            .col-head, .feed-header-sticky {
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
            .profile-sticky-bar {
                top: 99px;
            }
            .profile-cover {
                height: 120px;
            }
            .profile-hero-row .avatar-lg {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                margin-top: -40px;
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
            color: var(--success);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 0.95rem;
            font-weight: 700;
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