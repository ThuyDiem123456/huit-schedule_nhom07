<?php
declare(strict_types=1);

use Huit\Scheduler;

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_NAME'] ?? 'huit_db';
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('HUIT database: ' . $exception->getMessage());
        http_response_code(503);
        exit('Không kết nối được MySQL. Hãy kiểm tra Laragon, file .env và import database/huit_db.sql.');
    }
    return $pdo;
}
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function app_url(string $page = 'dashboard', array $params = []): string { return 'index.php?' . http_build_query(['page' => $page] + $params); }
function redirect_to(string $page, array $params = []): never { header('Location: ' . app_url($page, $params)); exit; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function signed_in(): bool { return current_user() !== null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_login(): void { if (!signed_in()) redirect_to('login'); }
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('403 – Bạn không có quyền truy cập chức năng này.'); } }
function csrf_token(): string { if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Phiên biểu mẫu đã hết hạn. Vui lòng tải lại trang.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function take_flashes(): array { $messages = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $messages; }
function rows(string $sql, array $parameters = []): array { $stmt = db()->prepare($sql); $stmt->execute($parameters); return $stmt->fetchAll(); }
function one(string $sql, array $parameters = []): ?array { $stmt = db()->prepare($sql); $stmt->execute($parameters); return $stmt->fetch() ?: null; }
function execute_sql(string $sql, array $parameters = []): int { $stmt = db()->prepare($sql); $stmt->execute($parameters); return $stmt->rowCount(); }
function day_names(): array { return [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật']; }
function short_day(int $day): string { return [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'][$day] ?? '—'; }
function day_label(int $day): string { return day_names()[$day] ?? 'Không xác định'; }
function period_label(int $start, int $end): string { return 'Tiết ' . $start . ($start === $end ? '' : '–' . $end); }
function chosen(mixed $a, mixed $b): string { return (string)$a === (string)$b ? ' selected' : ''; }
function page_title(string $page): string { return ['dashboard'=>'Tổng quan','schedules'=>'Lịch giảng dạy','schedule-form'=>'Phân công lịch dạy','teachers'=>'Giảng viên','teacher-form'=>'Thông tin giảng viên','branches'=>'Cơ sở đào tạo','branch-form'=>'Thông tin cơ sở','my-schedule'=>'Lịch dạy cá nhân','login'=>'Đăng nhập','profile'=>'Tài khoản cá nhân'][$page] ?? 'HUIT Schedule Hub'; }
function current_page(): string { return preg_replace('/[^a-z-]/', '', (string)($_GET['page'] ?? 'dashboard')) ?: 'dashboard'; }
function input(string $key, string $default = ''): string { return trim((string)($_POST[$key] ?? $default)); }
function query_int(string $key, int $default = 0): int { return filter_var($_GET[$key] ?? $default, FILTER_VALIDATE_INT) !== false ? (int)($_GET[$key] ?? $default) : $default; }
function check_length(string $value, string $label, int $max, int $min = 1): void { if (mb_strlen($value) < $min || mb_strlen($value) > $max) throw new DomainException("{$label} phải có {$min}–{$max} ký tự."); }
function initials(string $name): string { $parts = preg_split('/\s+/u', trim($name)) ?: []; return mb_strtoupper(mb_substr((string)(end($parts) ?: '?'), 0, 1)); }
function go_back_error(string $message, string $page, array $params = []): never { $_SESSION['old_form'] = $_POST; unset($_SESSION['old_form']['password'], $_SESSION['old_form']['old_password'], $_SESSION['old_form']['new_password'], $_SESSION['old_form']['confirm_password']); flash('error', $message); redirect_to($page, $params); }
function old_form(array $record = []): array { $old = $_SESSION['old_form'] ?? null; unset($_SESSION['old_form']); return is_array($old) ? array_merge($record, $old) : $record; }
function format_week(DateTimeImmutable $date): string { $monday = $date->modify('monday this week'); return $monday->format('d/m') . ' – ' . $monday->modify('+6 days')->format('d/m/Y'); }
function safe_date(string $value): DateTimeImmutable { $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value); return $date && $date->format('Y-m-d') === $value ? $date : new DateTimeImmutable('today'); }
function role_label(string $role): string { return $role === 'admin' ? 'Quản trị viên' : 'Giảng viên'; }
function timetable_conflicts(array $candidate, int $skip = 0): array
{
    $all = rows('SELECT * FROM schedules WHERE day_of_week = ? AND (teacher_id = ? OR branch_id = ?)', [(int)$candidate['day_of_week'], (int)$candidate['teacher_id'], (int)$candidate['branch_id']]);
    $issues = [];
    foreach ($all as $existing) {
        if ((int)$existing['id'] === $skip) continue;
        if ((int)$existing['teacher_id'] === (int)$candidate['teacher_id'] && Scheduler::teacherConflict($candidate, $existing)) $issues[] = 'Giảng viên trùng tiết hoặc không đủ thời gian di chuyển giữa hai cơ sở (lịch #' . $existing['id'] . ').';
        if (Scheduler::roomConflict($candidate, $existing)) $issues[] = 'Phòng ' . $candidate['room'] . ' đã có lịch tại cơ sở này (lịch #' . $existing['id'] . ').';
    }
    return array_unique($issues);
}
