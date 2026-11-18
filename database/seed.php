<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Only CLI is allowed.\n"); }
require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $pdo = db();
    if (!one('SELECT id FROM branches LIMIT 1')) {
        echo "No sample branches found. Import database/huit_db.sql in HeidiSQL first.\n";
        exit(1);
    }
    $pdo->beginTransaction();
    // Accounts are created with secure hashes rather than a hard-coded SQL hash.
    if (!one("SELECT id FROM users WHERE username='admin'")) {
        execute_sql('INSERT INTO users (username,password_hash,role) VALUES (?,?,?)', ['admin', password_hash('Admin@123', PASSWORD_DEFAULT), 'admin']);
    }
    $teachers = rows('SELECT id,email FROM teachers');
    foreach ($teachers as $t) {
        if (one('SELECT id FROM users WHERE teacher_id=?', [$t['id']])) continue;
        $name = strtolower((string)explode('@', $t['email'])[0]);
        if (one('SELECT id FROM users WHERE username=?', [$name])) $name .= '_' . $t['id'];
        execute_sql('INSERT INTO users (username,password_hash,role,teacher_id) VALUES (?,?,?,?)', [$name, password_hash('Teacher@123', PASSWORD_DEFAULT), 'teacher', $t['id']]);
    }
    // The required open-source Faker package generates additional demo teachers.
    $faker = Faker\Factory::create('vi_VN');
    $skills = ['Lập trình Python', 'Thiết kế đồ họa', 'Cấu trúc dữ liệu', 'Quản trị mạng'];
    for ($i = 1; $i <= 4; $i++) {
        $email = 'fake-teacher-' . $i . '@example.test';
        if (one('SELECT id FROM teachers WHERE email=?', [$email])) continue;
        execute_sql('INSERT INTO teachers (full_name,email,phone,expertise) VALUES (?,?,?,?)', [
            $faker->name(), $email, $faker->numerify('09########'), $skills[$i - 1],
        ]);
        $teacherId = (int)$pdo->lastInsertId();
        execute_sql('INSERT INTO users (username,password_hash,role,teacher_id) VALUES (?,?,?,?)', [
            'demo_gv_' . $i, password_hash('Teacher@123', PASSWORD_DEFAULT), 'teacher', $teacherId,
        ]);
        execute_sql('INSERT INTO schedules (teacher_id,branch_id,subject_name,room,day_of_week,shift_or_periods,period_start,period_end) VALUES (?,?,?,?,?,?,?,?)', [
            $teacherId, ($i % 3) + 1, $skills[$i - 1], 'DEMO' . $i, $i + 1, '1-3', 1, 3,
        ]);
    }
    $pdo->commit();
    echo "\nHUIT Schedule Hub seed completed!\n";
    echo "Admin     : admin / Admin@123\n";
    echo "Teacher   : minhanh / Teacher@123\n";
    echo "Faker demo: demo_gv_1 / Teacher@123\n";
    echo "IMPORTANT: Change demo passwords in the Profile page.\n\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Seed failed: {$e->getMessage()}\n");
    exit(1);
}
