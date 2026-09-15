<?php
$u = current_user();
$app = $GLOBALS['MAWARID_CONFIG']['app_name'] ?? 'موارد';
$pageTitle = $pageTitle ?? $app;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | <?= h($app) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="logo-mark">م</span>
            <div>
                <strong><?= h($app) ?></strong>
                <small>نظام الموارد البشرية</small>
            </div>
        </div>
        <nav class="side-nav">
            <a class="<?= nav_active('index.php') ?>" href="index.php">لوحة التحكم</a>
            <?php if (can('employees.view') || can('employees.view_all')): ?>
                <a class="<?= nav_active('employees.php') ?>" href="employees.php">الموظفون</a>
            <?php endif; ?>
            <?php if (can('departments.manage') || can('employees.view_all') || ($u['role_slug'] ?? '') === 'dept_manager'): ?>
                <a class="<?= nav_active('departments.php') ?>" href="departments.php">الأقسام</a>
            <?php endif; ?>
            <?php if (can('leaves.view') || can('leaves.create') || can('leaves.approve')): ?>
                <a class="<?= nav_active('leaves.php') ?>" href="leaves.php">الإجازات</a>
            <?php endif; ?>
            <?php if (can('attendance.view') || can('attendance.record')): ?>
                <a class="<?= nav_active('attendance.php') ?>" href="attendance.php">الحضور</a>
            <?php endif; ?>
            <?php if (can('users.manage')): ?>
                <a class="<?= nav_active('users.php') ?>" href="users.php">المستخدمون</a>
            <?php endif; ?>
            <a class="<?= nav_active('profile.php') ?>" href="profile.php">حسابي</a>
        </nav>
        <div class="side-user">
            <div class="avatar"><?= h(mb_substr($u['name'] ?? 'م', 0, 1)) ?></div>
            <div>
                <strong><?= h($u['name'] ?? '') ?></strong>
                <small><?= h($u['role_name'] ?? '') ?></small>
            </div>
            <a class="logout" href="logout.php">خروج</a>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="menu-btn" type="button" id="menuBtn" aria-label="القائمة">☰</button>
            <h1><?= h($pageTitle) ?></h1>
            <div class="top-user"><?= h($u['name'] ?? '') ?></div>
        </header>
        <main class="content">
            <?php if ($msg = flash('success')): ?>
                <div class="alert success"><?= h($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert error"><?= h($msg) ?></div>
            <?php endif; ?>
