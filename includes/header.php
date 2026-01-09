<?php
// Simple header logic
$title = isset($pageTitle) ? $pageTitle : 'Sistema de Matrícula';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - IE Las Capullanas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="top-header">
            <div class="school-info">
                <div class="school-icon" style="width: 50px; height: 50px; padding: 0;">
                    <img src="assets/images/logo.png" alt="Logo IE Las Capullanas" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="logo-text">
                    <h1>IE LAS CAPULLANAS <br><span>MATRÍCULA 2026</span></h1>
                </div>
            </div>

            <div class="user-actions">
                <div class="admin-badge">
                    <small>ADMINISTRADOR GENERAL</small>
                    <span class="admin-tag">ADMIN</span>
                </div>
                <button class="logout-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </div>
        </header>

        <div class="content-wrapper">
