<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FF3D33">
    <title><?= e($title ?? \App\Support\Branding::name()); ?></title>
    <link href="<?= e(asset('css/bootstrap.min.css')); ?>" rel="stylesheet">
    <link href="<?= e(asset('css/bootstrap-icons.min.css')); ?>" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')); ?>" rel="stylesheet">
    <?php $__bc = \App\Support\Branding::brandColor(); if ($__bc !== '' && $__bc !== '#ff3d33'): ?>
    <style>:root { --brand-primary: <?= e($__bc); ?>; }</style>
    <?php endif; ?>
</head>
<body class="auth-body">

    <div class="auth-outer">
        <div class="auth-inner">

            <div class="auth-brand-mark">
                <img src="<?= e(url('/assets/images/peoplova-mark-white.svg')); ?>"
                     alt="<?= e(\App\Support\Branding::name()); ?>"
                     class="auth-mark-img">
                <span class="auth-mark-name"><?= e(\App\Support\Branding::name()); ?></span>
            </div>
            <p class="auth-brand-tagline"><?= e(\App\Support\Branding::tagline()); ?></p>

            <?php require base_path('app/Views/partials/flash.php'); ?>

            <?= $content; ?>

            <div class="auth-quick-links">
                <a href="<?= e(url('/dashboard')); ?>" class="auth-quick-link">
                    <i class="bi bi-grid-fill"></i> HR Portal
                </a>
                <a href="<?= e(url('/leave/request')); ?>" class="auth-quick-link">
                    <i class="bi bi-calendar-plus"></i> Request Leave
                </a>
                <a href="<?= e(url('/careers')); ?>" class="auth-quick-link">
                    <i class="bi bi-briefcase-fill"></i> Careers
                </a>
            </div>

            <p class="auth-page-footer">
                &copy; <?= date('Y'); ?> <?= e(\App\Support\Branding::name()); ?> &mdash; All rights reserved.
            </p>

        </div>
    </div>

    <script src="<?= e(asset('js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>
