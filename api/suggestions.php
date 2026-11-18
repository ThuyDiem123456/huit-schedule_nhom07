<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use Huit\Scheduler;
header('Content-Type: application/json; charset=utf-8');
if (!is_admin()) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit; }
$day = (int)($_GET['day'] ?? 0); $start = (int)($_GET['start'] ?? 0); $end = (int)($_GET['end'] ?? 0);
$branch = (int)($_GET['branch'] ?? 0); $subject = trim((string)($_GET['subject'] ?? ''));
$skip = (int)($_GET['skip'] ?? 0);
if (!Scheduler::validSlot($day, $start, $end) || $branch < 1 || $subject === '') { echo json_encode(['recommendations' => [], 'message' => 'Vui lòng chọn cơ sở, học phần, thứ và khoảng tiết hợp lệ.'], JSON_UNESCAPED_UNICODE); exit; }
$all = rows('SELECT * FROM schedules');
$candidate = ['branch_id' => $branch, 'day_of_week' => $day, 'period_start' => $start, 'period_end' => $end, 'subject_name' => $subject];
$results = [];
foreach (rows('SELECT * FROM teachers ORDER BY full_name') as $teacher) {
    if (!Scheduler::expertiseMatch($teacher['expertise'], $subject)) continue;
    $proposed = $candidate + ['teacher_id' => (int)$teacher['id']];
    if (!Scheduler::available($proposed, $all, $skip)) continue;
    $score = Scheduler::score($teacher, array_filter($all, fn(array $s): bool => (int)$s['id'] !== $skip), $proposed);
    $results[] = ['id' => (int)$teacher['id'], 'name' => $teacher['full_name'], 'expertise' => $teacher['expertise'], 'score' => $score];
}
usort($results, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
echo json_encode(['recommendations' => array_slice($results, 0, 8), 'message' => $results ? 'Sắp xếp theo độ phù hợp, tiết trống và mức độ thuận tiện di chuyển.' : 'Chưa có giảng viên đúng chuyên môn và trống lịch trong khung giờ này.'], JSON_UNESCAPED_UNICODE);
