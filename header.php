<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Management System</title>

    <link rel="stylesheet" href="/library_management_system/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<header class="lms-header">
    <div class="lms-wrapper">

        <!-- Brand -->
        <div class="lms-brand">
            Library<span>Management</span>System
        </div>

        <!-- Navigation -->
        <nav class="lms-nav">

            <?php if (!isset($_SESSION['role'])): ?>
                <!-- Public Navbar -->
                <a href="/library_management_system/index.php"
                   class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>

                <a href="/library_management_system/about.php"
                   class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a>
                   
                <a href="/library_management_system/services.php"
                class="<?= $currentPage === 'services.php'? 'active' : '' ?>">Services</a>
                
                <a href="/library_management_system/contact.php"
                class="<?= $currentPage === 'contact.php'? 'active' : '' ?>">Contact</a>

                <a href="/library_management_system/admin/admin_login.php"
                   class="<?= $currentPage === 'admin_login.php' ? 'active' : '' ?>">
                    Admin Login
                </a>

            <?php elseif ($_SESSION['role'] === 'student'): ?>
                <!-- Student Navbar -->
                <a href="/library_management_system/student/student_dashboard.php"
                   class="<?= $currentPage === 'student_dashboard.php' ? 'active' : '' ?>">
                    Dashboard
                </a>

                <a href="/library_management_system/student/available_books.php"
                   class="<?= $currentPage === 'available_books.php' ? 'active' : '' ?>">
                    Books
                </a>

                <a href="/library_management_system/student/donate_book.php"
                   class="<?= $currentPage === 'donate_book.php' ? 'active' : '' ?>">
                    Donate
                </a>

                
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <!-- Admin Navbar -->
                <a href="/library_management_system/admin/admin_dashboard.php"
                   class="<?= $currentPage === 'admin_dashboard.php' ? 'active' : '' ?>">
                    Dashboard
                </a>

            <?php endif; ?>

        </nav>

        <!-- Action Buttons -->
        <div class="lms-actions">
            <?php if (!isset($_SESSION['role'])): ?>
                <a href="/library_management_system/student/student_login.php" class="btn-login">
                    Login
                </a>
                <a href="/library_management_system/student/student_register.php" class="btn-register">
                    Register
                </a>
            <?php else: ?>
                <a href="/library_management_system/logout.php" class="btn-logout">
                    Logout
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<main class="lms-content">