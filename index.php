<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/actions.php';

$page = current_page();
$allowed = ['dashboard', 'schedules', 'schedule-form', 'teachers', 'teacher-form', 'branches', 'branch-form', 'my-schedule', 'login', 'profile'];
if (!in_array($page, $allowed, true)) { http_response_code(404); exit('404 – Trang không tồn tại.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { handle_action(); }
if (!signed_in() && $page !== 'login') redirect_to('login');
if (signed_in() && $page === 'login') redirect_to(is_admin() ? 'dashboard' : 'my-schedule');
if (signed_in() && !is_admin() && !in_array($page, ['my-schedule', 'profile'], true)) redirect_to('my-schedule');

if ($page === 'login') {
    require __DIR__ . '/views/login.php';
} else {
    require __DIR__ . '/views/layout-top.php';
    require __DIR__ . '/views/' . $page . '.php';
    require __DIR__ . '/views/layout-bottom.php';
}
