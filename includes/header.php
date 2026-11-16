<?php
/**
 * HEADER DÙNG CHUNG CHO CLIENT (includes/header.php)
 * Tích hợp Bootstrap 5.3.2, Bootstrap Icons và Google Font
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/helpers.php';

$currentUser = $_SESSION['user'] ?? null;
$isLoggedIn  = !empty($currentUser);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'AURA STUDIO - Thời trang & Khách hàng thân thiết') ?></title>
  
  <!-- Bootstrap 5 CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Google Font: Be Vietnam Pro -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Be Vietnam Pro', system-ui, sans-serif;
      background-color: #FAF9F6;
      color: #2D2D2D;
    }
    .brand-letter-spacing {
      letter-spacing: 0.25em;
    }
    .nav-link {
      font-weight: 500;
      color: #52525b;
      transition: color 0.2s ease;
    }
    .nav-link:hover, .nav-link.active {
      color: #18181b !important;
    }
    .btn-aura {
      background-color: #2D2D2D;
      color: #ffffff;
      border: none;
      transition: background-color 0.2s ease, transform 0.15s ease;
    }
    .btn-aura:hover {
      background-color: #8C6D53;
      color: #ffffff;
    }
    .btn-aura:active {
      transform: scale(0.98);
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">
  <?php require __DIR__ . '/navbar.php'; ?>
  <main class="flex-grow-1">
