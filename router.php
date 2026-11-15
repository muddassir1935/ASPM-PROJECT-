<?php
/**
 * Router for PHP's built-in development server (audit C5).
 *
 *   php -S localhost:8000 router.php
 *
 * The built-in server ignores .htaccess, so without this router anyone
 * could download db/seed.sql (password hashes), .git/ (full source and
 * history) or docs/, and requesting tests/run_all.php would execute the
 * whole test suite. This mirrors the .htaccess deny rules: internal
 * directories, dotfiles and sensitive file types get a 404; everything
 * else is served normally.
 */
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

$denied =
    // Dotfiles / dot-directories anywhere (.git, .env, .htaccess, .agents ...)
    preg_match('#(^|/)\.#', $path) === 1
    // Internal directories — PHP entry points live at the root only
    || preg_match('#^/(config|classes|db|docs|tests|scripts|includes)(/|$)#i', $path) === 1
    // Sensitive file types anywhere
    || preg_match('#\.(sqlite|sqlite3|db|sql|md|py|sh|log)$#i', $path) === 1
    // The router itself is not a page
    || strcasecmp($path, '/router.php') === 0;

if ($denied) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Not Found";
    return true;
}

// Let the built-in server handle the request (static file or PHP page).
return false;
