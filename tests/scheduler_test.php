<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Scheduler.php';
use Huit\Scheduler as S;
$count = 0;
function check(bool $ok, string $name): void {
    global $count;
    if (!$ok) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
    $count++;
    echo "PASS: {$name}\n";
}
$base = ['id'=>1,'teacher_id'=>1,'branch_id'=>1,'day_of_week'=>1,'room'=>'A201','subject_name'=>'Lập trình PHP','period_start'=>1,'period_end'=>3];
check(S::validSlot(1,1,3),'First day is Monday');
check(S::validSlot(7,10,12),'Sunday, period 12');
check(!S::validSlot(0,1,3),'Reject invalid day');
check(!S::validSlot(2,6,4),'Reject reverse interval');
check(!S::validSlot(2,11,13),'Reject period 13');
check(S::overlaps(1,3,3,4),'Inclusive overlap');
check(!S::overlaps(1,3,4,6),'Adjacent intervals do not overlap');
check(S::teacherConflict($base,$base),'Teacher cannot overlap');
check(!S::teacherConflict($base,array_merge($base,['day_of_week'=>2])),'Different day can reuse period');
check(!S::teacherConflict($base,array_merge($base,['period_start'=>4,'period_end'=>6])),'Same-campus back-to-back allowed');
check(S::teacherConflict($base,array_merge($base,['branch_id'=>2,'period_start'=>4,'period_end'=>6])),'Cross-campus back-to-back blocked');
check(!S::teacherConflict($base,array_merge($base,['branch_id'=>2,'period_start'=>5,'period_end'=>7])),'Cross-campus with one free period allowed');
check(S::roomConflict($base,array_merge($base,['teacher_id'=>2,'room'=>'a201','period_start'=>3,'period_end'=>6])),'Room collision case-insensitive');
check(!S::roomConflict($base,array_merge($base,['branch_id'=>2])),'Same room name at another campus allowed');
check(S::expertiseMatch('Lập trình PHP, Phát triển Web','Lập trình PHP'),'Vietnamese expertise exact');
check(S::expertiseMatch('Trí tuệ nhân tạo, Python','Nhập môn Python'),'Expertise contains subject keyword');
check(!S::expertiseMatch('Cơ sở dữ liệu, MySQL','Lập trình PHP'),'Unrelated skill not recommended');
check(S::available($base,[$base],1),'Updating schedule can ignore itself');
check(!S::available($base,[$base],0),'Availability blocks identical existing schedule');
check(S::score(['id'=>1,'expertise'=>'Lập trình PHP'],[],$base)>S::score(['id'=>1,'expertise'=>'MySQL'],[],$base),'Matching skills produce higher score');
echo "\nAll {$count} scheduler tests passed.\n";
