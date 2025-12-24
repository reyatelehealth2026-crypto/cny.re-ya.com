<?php
/**
 * PWA Head Tags - Include this in all pages for PWA support
 */
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!-- PWA Meta Tags -->
<meta name="application-name" content="LINE OA Manager">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="LINE OA">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#06C755">
<meta name="msapplication-TileColor" content="#06C755">
<meta name="msapplication-tap-highlight" content="no">

<!-- PWA Manifest -->
<link rel="manifest" href="<?= $baseUrl ?>/manifest.json">

<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/icons/icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $baseUrl ?>/assets/icons/icon-192x192.png">

<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="<?= $baseUrl ?>/assets/icons/icon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/icons/icon-16x16.png">

<!-- Splash Screens for iOS -->
<link rel="apple-touch-startup-image" href="<?= $baseUrl ?>/assets/splash/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px)">
<link rel="apple-touch-startup-image" href="<?= $baseUrl ?>/assets/splash/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px)">
<link rel="apple-touch-startup-image" href="<?= $baseUrl ?>/assets/splash/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px)">
<link rel="apple-touch-startup-image" href="<?= $baseUrl ?>/assets/splash/splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px)">
