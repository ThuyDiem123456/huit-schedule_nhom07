<?php
$account = current_user();
$active = current_page();
$person = $account['role'] === 'teacher' ? one('SELECT full_name FROM teachers WHERE id = ?', [$account['teacher_id']]) : null;
$displayName = $person['full_name'] ?? 'Phòng Đào tạo';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102b46">
  <title><?= e(page_title($active)) ?> · HUIT Schedule Hub</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/app.css">
  <script src="assets/app.js" defer></script>
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" class="svg-sprite" aria-hidden="true">
  <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.4"/><rect x="14" y="3" width="7" height="7" rx="1.4"/><rect x="3" y="14" width="7" height="7" rx="1.4"/><rect x="14" y="14" width="7" height="7" rx="1.4"/></symbol>
  <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18M7 14h3M7 17h3M14 14h3"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-2a6.5 6.5 0 0 1 13 0v2M17 5a3.5 3.5 0 0 1 0 7M17 15a5 5 0 0 1 4.5 5"/></symbol>
  <symbol id="i-building" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-5h6v5M8 7h2M14 7h2M8 11h2M14 11h2"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></symbol>
  <symbol id="i-filter" viewBox="0 0 24 24"><path d="M4 7h16M7 12h10M10 17h4"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
  <symbol id="i-logout" viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 16l4-4-4-4M8 12h10"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
  <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/></symbol>
  <symbol id="i-spark" viewBox="0 0 24 24"><path d="m12 2 2.1 6.8L21 11l-6.9 2.2L12 20l-2.1-6.8L3 11l6.9-2.2L12 2ZM20 18v4M18 20h4"/></symbol>
  <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></symbol>
</svg>
<div class="app-shell" id="appShell">
  <aside class="sidebar" aria-label="Điều hướng">
    <a class="brand" href="<?= e(app_url(is_admin() ? 'dashboard' : 'my-schedule')) ?>">
      <span class="brand-mark">H</span><span><strong>HUIT<span class="brand-dot">.</span></strong><small>SCHEDULE HUB</small></span>
    </a>
    <div class="sidebar-section-label">KHÔNG GIAN LÀM VIỆC</div>
    <nav class="side-nav">
      <?php if (is_admin()): ?>
      <a class="nav-link<?= $active === 'dashboard' ? ' active' : '' ?>" href="<?= e(app_url('dashboard')) ?>"><svg><use href="#i-grid"/></svg>Tổng quan</a>
      <a class="nav-link<?= in_array($active, ['schedules','schedule-form'], true) ? ' active' : '' ?>" href="<?= e(app_url('schedules')) ?>"><svg><use href="#i-calendar"/></svg>Lịch giảng dạy</a>
      <a class="nav-link<?= in_array($active, ['teachers','teacher-form'], true) ? ' active' : '' ?>" href="<?= e(app_url('teachers')) ?>"><svg><use href="#i-users"/></svg>Giảng viên</a>
      <a class="nav-link<?= in_array($active, ['branches','branch-form'], true) ? ' active' : '' ?>" href="<?= e(app_url('branches')) ?>"><svg><use href="#i-building"/></svg>Cơ sở đào tạo</a>
      <?php else: ?>
      <a class="nav-link<?= $active === 'my-schedule' ? ' active' : '' ?>" href="<?= e(app_url('my-schedule')) ?>"><svg><use href="#i-calendar"/></svg>Lịch dạy của tôi</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-section-label second">HỆ THỐNG</div>
    <nav class="side-nav"><a class="nav-link<?= $active === 'profile' ? ' active' : '' ?>" href="<?= e(app_url('profile')) ?>"><svg><use href="#i-user"/></svg>Tài khoản</a></nav>
    <div class="sidebar-bottom">
      <div class="sidebar-info"><span class="online-dot"></span><span>Hệ thống đang hoạt động<br><small>Phiên bản bài thực hành · 1.0</small></span></div>
      <form method="post" action="<?= e(app_url($active)) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="nav-link logout-link" type="submit"><svg><use href="#i-logout"/></svg>Đăng xuất</button></form>
    </div>
  </aside>
  <div class="main-shell">
    <header class="topbar"><button type="button" class="mobile-menu" id="menuToggle" aria-label="Mở menu">☰</button><div class="topbar-path">HUIT Schedule Hub <span>/</span> <strong><?= e(page_title($active)) ?></strong></div><div class="topbar-right"><span class="topbar-date"><?= e(date('d/m/Y')) ?></span><span class="top-avatar"><?= e(initials($displayName)) ?></span><span class="top-user"><strong><?= e($displayName) ?></strong><small><?= e(role_label($account['role'])) ?></small></span></div></header>
    <main class="page-content">
      <?php foreach (take_flashes() as $alert): ?><div class="alert alert-<?= e($alert['type']) ?>"><span class="alert-icon"><?= $alert['type'] === 'success' ? '✓' : '!' ?></span><span><?= e($alert['message']) ?></span><button type="button" class="alert-close" aria-label="Đóng thông báo">×</button></div><?php endforeach; ?>
