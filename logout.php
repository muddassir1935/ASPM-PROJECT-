<?php
/**
 * Logout (Sprint 5: CSRF-protected).
 *
 * Logout is a state change, so it is a POST with a CSRF token like
 * every other action — the header renders it as a small form, not a
 * GET link. A stray GET (old bookmark, crawler) simply redirects to
 * the feed instead of logging the user out.
 */
require_once 'classes/Auth.php';
require_once 'classes/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::validatePost()) {
    Auth::logout();
    header("Location: login.php?logged_out=1");
    exit;
}

header("Location: index.php");
exit;
