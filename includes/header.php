<?php
if(file_exists(__DIR__ . '/../classes/Auth.php')) {
    require_once __DIR__ . '/../classes/Auth.php';
}
$currentUser = class_exists('Auth') ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect — University Microblogging Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #059669;
            --primary-hover: #047857;
            --primary-light: #ecfdf5;
            --primary-ring: rgba(5, 150, 105, 0.2);
            --dark: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --border: #e2e8f0;
            --radius-md: 10px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--slate-50);
            color: var(--slate-800);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
        }

        /* Modern Navbar */
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 0.9rem 2rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--dark);
        }

        .brand-logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
        }

        .brand-text {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--dark);
        }

        .brand-badge {
            background: var(--primary-light);
            color: var(--primary);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: 6px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            list-style: none;
        }

        .nav-link {
            text-decoration: none;
            color: var(--slate-600);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 14px;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background-color: var(--primary-light);
        }

        .btn-nav-primary {
            background-color: var(--primary);
            color: #ffffff !important;
            padding: 8px 18px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
        }

        .btn-nav-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .user-tag {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--slate-700);
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--slate-100);
            padding: 6px 12px;
            border-radius: 20px;
        }

        .user-dot {
            width: 8px;
            height: 8px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        /* Main Container */
        .page-content {
            flex: 1;
            max-width: 1100px;
            width: 100%;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        /* Auth Card Layout */
        .auth-container {
            max-width: 440px;
            margin: 1.5rem auto;
        }

        .auth-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 2.25rem;
            box-shadow: var(--shadow-xl);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .auth-icon-circle {
            width: 52px;
            height: 52px;
            background: var(--primary-light);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .auth-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.02em;
        }

        .auth-subtitle {
            color: var(--slate-500);
            font-size: 0.88rem;
            margin-top: 4px;
        }

        /* Form Components */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            font-size: 0.93rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            background-color: #ffffff;
            color: var(--slate-800);
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-ring);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(5, 150, 105, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .auth-footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.88rem;
            color: var(--slate-500);
        }

        .auth-footer-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .auth-footer-text a:hover {
            text-decoration: underline;
        }

        /* Alert Banners */
        .alert {
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Footer */
        .site-footer {
            background: #ffffff;
            border-top: 1px solid var(--border);
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--slate-500);
            margin-top: auto;
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="brand-link">
            <div class="brand-logo-icon">C</div>
            <div>
                <span class="brand-text">CampusConnect</span>
            </div>
        </a>

        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <?php if($currentUser): ?>
                <li>
                    <span class="user-tag">
                        <span class="user-dot"></span>
                        <?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($currentUser['role']) ?>)
                    </span>
                </li>
                <li><a href="logout.php" class="nav-link" style="color: #dc2626;">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link">Log In</a></li>
                <li><a href="signup.php" class="btn-nav-primary">Create Account</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<main class="page-content">