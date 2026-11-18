<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use Huit\Scheduler;
header('Content-Type: application/json; charset=utf-8');
if (!is_admin()) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit; }
$teacher = (int)($_GET['teacher'] ?? 0); $day = (int)($_GET['day'] ?? 0); $branch = (int)($_GET['branch'] ?? 0);
$skip = (int)($_GET['skip'] ?? 0);
if (!$teacher || $day < 1 || $day > 7 || !$branch) { echo json_encode(['periods' => []]); exit; }
$all = rows('SELECT s.*, b.branch_name FROM schedules s JOIN branches b ON b.id=s.branch_id WHERE s.teacher_id=? AND s.day_of_week=? AND s.id <> ?', [$teacher, $day, $skip]);
$periods=[];
for ($i=1; $i<=12; $i++) {
    $candidate=['teacher_id'=>$teacher,'branch_id'=>$branch,'day_of_week'=>$day,'period_start'=>$i,'period_end'=>$i];
    $busy=[];
    foreach ($all as $existing) {
        if (Scheduler::teacherConflict($candidate, $existing)) $busy[] = $existing['subject_name'].' · '.$existing['branch_name'].' · Tiết '.$existing['period_start'].'–'.$existing['period_end'];
    }
    $periods[]=['period'=>$i,'available'=>!$busy,'description'=>implode('; ', $busy)];
}
echo json_encode(['periods'=>$periods], JSON_UNESCAPED_UNICODE);
