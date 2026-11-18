<?php
declare(strict_types=1);

use Huit\Scheduler;

function handle_action(): never
{
    verify_csrf();
    $action = input('action');
    if ($action === 'login') {
        login_action();
    }
    require_login();
    if ($action === 'logout') {
        unset($_SESSION['user'], $_SESSION['csrf']);
        session_regenerate_id(true);
        flash('success', 'Đã đăng xuất an toàn.');
        redirect_to('login');
    }
    if ($action === 'change-password') {
        change_password_action();
    }
    require_admin();
    $page = match ($action) {
        'save-branch', 'delete-branch' => 'branch-form',
        'save-teacher', 'delete-teacher' => 'teacher-form',
        'save-schedule', 'delete-schedule' => 'schedule-form',
        default => 'dashboard',
    };
    $params = input('id') !== '' ? ['id' => (int)input('id')] : [];
    try {
        switch ($action) {
            case 'save-branch': save_branch(); flash('success', 'Đã lưu thông tin cơ sở đào tạo.'); redirect_to('branches');
            case 'delete-branch': delete_branch(); flash('success', 'Đã xóa cơ sở đào tạo.'); redirect_to('branches');
            case 'save-teacher': save_teacher(); flash('success', 'Đã lưu thông tin và tài khoản giảng viên.'); redirect_to('teachers');
            case 'delete-teacher': delete_teacher(); flash('success', 'Đã xóa giảng viên và tài khoản liên kết.'); redirect_to('teachers');
            case 'save-schedule': save_schedule(); flash('success', 'Đã lưu lịch dạy và kiểm tra xung đột thành công.'); redirect_to('schedules');
            case 'delete-schedule': delete_schedule(); flash('success', 'Đã xóa lịch giảng dạy.'); redirect_to('schedules');
            default: throw new DomainException('Thao tác không hợp lệ.');
        }
    } catch (DomainException $exception) {
        go_back_error($exception->getMessage(), $page, $params);
    } catch (PDOException $exception) {
        error_log('HUIT SQL: ' . $exception->getMessage());
        go_back_error('Không thể lưu dữ liệu. Có thể tên đăng nhập/email đã được sử dụng hoặc bản ghi đang được liên kết.', $page, $params);
    } catch (Throwable $exception) {
        error_log('HUIT app: ' . $exception->getMessage());
        go_back_error('Đã xảy ra lỗi. Vui lòng kiểm tra dữ liệu và thử lại.', $page, $params);
    }
}

function login_action(): never
{
    $username = input('username');
    $password = (string)($_POST['password'] ?? '');
    $attempts = $_SESSION['login_attempts'] ?? [];
    $attempts = array_values(array_filter($attempts, static fn(int $time): bool => $time > time() - 60));
    if (count($attempts) >= 5) {
        $_SESSION['login_attempts'] = $attempts;
        flash('error', 'Bạn đăng nhập sai quá nhiều lần. Hãy thử lại sau 60 giây.');
        redirect_to('login');
    }
    $account = one('SELECT id, username, password_hash, role, teacher_id FROM users WHERE username = ? LIMIT 1', [$username]);
    if (!$account || !password_verify($password, $account['password_hash'])) {
        $attempts[] = time();
        $_SESSION['login_attempts'] = $attempts;
        flash('error', 'Tên đăng nhập hoặc mật khẩu không chính xác.');
        redirect_to('login');
    }
    session_regenerate_id(true);
    unset($_SESSION['login_attempts']);
    $_SESSION['user'] = ['id' => (int)$account['id'], 'username' => $account['username'], 'role' => $account['role'], 'teacher_id' => $account['teacher_id'] !== null ? (int)$account['teacher_id'] : null];
    flash('success', 'Chào mừng quay lại HUIT Schedule Hub!');
    redirect_to($account['role'] === 'admin' ? 'dashboard' : 'my-schedule');
}

function change_password_action(): never
{
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $account = one('SELECT password_hash FROM users WHERE id = ?', [current_user()['id']]);
    if (!$account || !password_verify($old, $account['password_hash'])) {
        flash('error', 'Mật khẩu hiện tại không đúng.'); redirect_to('profile');
    }
    if (strlen($new) < 8 || $new !== $confirm) {
        flash('error', 'Mật khẩu mới tối thiểu 8 ký tự và phải trùng với phần xác nhận.'); redirect_to('profile');
    }
    execute_sql('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), current_user()['id']]);
    session_regenerate_id(true);
    flash('success', 'Đã đổi mật khẩu thành công.');
    redirect_to('profile');
}

function save_branch(): void
{
    $id = (int)input('id');
    $name = input('branch_name'); $address = input('address');
    check_length($name, 'Tên cơ sở', 160);
    check_length($address, 'Địa chỉ', 255);
    if ($id > 0) {
        if (!one('SELECT id FROM branches WHERE id = ?', [$id])) throw new DomainException('Cơ sở không tồn tại.');
        execute_sql('UPDATE branches SET branch_name = ?, address = ? WHERE id = ?', [$name, $address, $id]);
    } else execute_sql('INSERT INTO branches (branch_name, address) VALUES (?, ?)', [$name, $address]);
}
function delete_branch(): void
{
    $id = (int)input('id');
    if ($id <= 0) throw new DomainException('Cơ sở không hợp lệ.');
    if (one('SELECT id FROM schedules WHERE branch_id = ? LIMIT 1', [$id])) throw new DomainException('Không thể xóa cơ sở đang có lịch dạy. Hãy chuyển/xóa lịch trước.');
    execute_sql('DELETE FROM branches WHERE id = ?', [$id]);
}

function save_teacher(): void
{
    $id = (int)input('id');
    $name = input('full_name'); $email = input('email');
    $phone = input('phone'); $expertise = input('expertise');
    $username = strtolower(input('username'));
    $password = (string)($_POST['password'] ?? '');
    check_length($name, 'Họ tên', 150);
    check_length($email, 'Email', 190);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Email chưa đúng định dạng.');
    if ($phone !== '') check_length($phone, 'Số điện thoại', 30);
    check_length($expertise, 'Chuyên môn', 255);
    if (!preg_match('/^[a-z0-9._-]{3,60}$/', $username)) throw new DomainException('Tên đăng nhập dài 3–60 ký tự, chỉ gồm chữ thường, số, dấu chấm, gạch dưới hoặc gạch ngang.');
    if (($id === 0 || $password !== '') && strlen($password) < 8) throw new DomainException('Mật khẩu tài khoản giảng viên phải có ít nhất 8 ký tự.');
    $pdo = db(); $pdo->beginTransaction();
    try {
        if ($id > 0) {
            if (!one('SELECT id FROM teachers WHERE id = ?', [$id])) throw new DomainException('Giảng viên không tồn tại.');
            execute_sql('UPDATE teachers SET full_name=?, email=?, phone=?, expertise=? WHERE id=?', [$name, $email, $phone, $expertise, $id]);
            $account = one('SELECT id FROM users WHERE teacher_id = ?', [$id]);
            if ($account) {
                if ($password !== '') execute_sql('UPDATE users SET username=?, password_hash=? WHERE id=?', [$username, password_hash($password, PASSWORD_DEFAULT), $account['id']]);
                else execute_sql('UPDATE users SET username=? WHERE id=?', [$username, $account['id']]);
            } else execute_sql('INSERT INTO users (username,password_hash,role,teacher_id) VALUES (?, ?, ?, ?)', [$username, password_hash($password !== '' ? $password : bin2hex(random_bytes(12)), PASSWORD_DEFAULT), 'teacher', $id]);
        } else {
            execute_sql('INSERT INTO teachers (full_name,email,phone,expertise) VALUES (?,?,?,?)', [$name, $email, $phone, $expertise]);
            $id = (int)$pdo->lastInsertId();
            execute_sql('INSERT INTO users (username,password_hash,role,teacher_id) VALUES (?,?,?,?)', [$username, password_hash($password, PASSWORD_DEFAULT), 'teacher', $id]);
        }
        $pdo->commit();
    } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
}
function delete_teacher(): void
{
    $id = (int)input('id');
    if ($id <= 0) throw new DomainException('Giảng viên không hợp lệ.');
    if (one('SELECT id FROM schedules WHERE teacher_id = ? LIMIT 1', [$id])) throw new DomainException('Không thể xóa giảng viên đang có lịch. Hãy phân công lại hoặc xóa lịch trước.');
    execute_sql('DELETE FROM teachers WHERE id = ?', [$id]);
}

function save_schedule(): void
{
    $id = (int)input('id');
    $teacher = (int)input('teacher_id'); $branch = (int)input('branch_id');
    $day = (int)input('day_of_week'); $start = (int)input('period_start'); $end = (int)input('period_end');
    $subject = input('subject_name'); $room = input('room');
    check_length($subject, 'Tên học phần', 190);
    check_length($room, 'Phòng học', 60);
    if ($teacher < 1 || $branch < 1) throw new DomainException('Vui lòng chọn giảng viên và cơ sở.');
    if (!Scheduler::validSlot($day, $start, $end)) throw new DomainException('Thứ/tiết không hợp lệ: chỉ cho phép từ Thứ Hai đến Chủ Nhật, tiết 1–12 và tiết bắt đầu ≤ tiết kết thúc.');
    $candidate = ['teacher_id' => $teacher, 'branch_id' => $branch, 'day_of_week' => $day, 'period_start' => $start, 'period_end' => $end, 'room' => $room, 'subject_name' => $subject];
    $pdo = db(); $pdo->beginTransaction();
    try {
        // Lock both entities so two concurrent submissions for the same teacher/branch serialize.
        if (!one('SELECT id FROM teachers WHERE id = ? FOR UPDATE', [$teacher])) throw new DomainException('Giảng viên không tồn tại.');
        if (!one('SELECT id FROM branches WHERE id = ? FOR UPDATE', [$branch])) throw new DomainException('Cơ sở không tồn tại.');
        if ($id > 0 && !one('SELECT id FROM schedules WHERE id = ? FOR UPDATE', [$id])) throw new DomainException('Lịch dạy không tồn tại.');
        $issues = timetable_conflicts($candidate, $id);
        if ($issues) throw new DomainException(implode(' ', $issues));
        if ($id > 0) execute_sql('UPDATE schedules SET teacher_id=?, branch_id=?, subject_name=?, room=?, day_of_week=?, shift_or_periods=?, period_start=?, period_end=? WHERE id=?', [$teacher, $branch, $subject, $room, $day, $start . '-' . $end, $start, $end, $id]);
        else execute_sql('INSERT INTO schedules (teacher_id,branch_id,subject_name,room,day_of_week,shift_or_periods,period_start,period_end) VALUES (?,?,?,?,?,?,?,?)', [$teacher, $branch, $subject, $room, $day, $start . '-' . $end, $start, $end]);
        $pdo->commit();
    } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
}
function delete_schedule(): void
{
    $id = (int)input('id');
    if ($id <= 0) throw new DomainException('Lịch dạy không hợp lệ.');
    execute_sql('DELETE FROM schedules WHERE id=?', [$id]);
}
