<?php
declare(strict_types=1);

namespace Huit;

/** Pure weekly scheduling logic. Monday=1 ... Sunday=7, periods 1..12. */
final class Scheduler
{
    public static function validSlot(int $day, int $start, int $end): bool
    {
        return $day >= 1 && $day <= 7 && $start >= 1 && $end <= 12 && $start <= $end;
    }

    public static function overlaps(int $as, int $ae, int $bs, int $be): bool
    {
        return $as <= $be && $bs <= $ae;
    }

    /**
     * Reject timetable overlap. Also require at least one FREE period when moving
     * between different campuses (e.g. finish P3, next campus can start P5).
     */
    public static function teacherConflict(array $candidate, array $existing): bool
    {
        if ((int)$candidate['day_of_week'] !== (int)$existing['day_of_week']) {
            return false;
        }
        $as = (int)$candidate['period_start'];
        $ae = (int)$candidate['period_end'];
        $bs = (int)$existing['period_start'];
        $be = (int)$existing['period_end'];
        if (self::overlaps($as, $ae, $bs, $be)) {
            return true;
        }
        return (int)$candidate['branch_id'] !== (int)$existing['branch_id']
            && ($ae + 1 === $bs || $be + 1 === $as);
    }

    public static function roomConflict(array $candidate, array $existing): bool
    {
        return (int)$candidate['day_of_week'] === (int)$existing['day_of_week']
            && (int)$candidate['branch_id'] === (int)$existing['branch_id']
            && self::normalize((string)$candidate['room']) === self::normalize((string)$existing['room'])
            && self::overlaps((int)$candidate['period_start'], (int)$candidate['period_end'], (int)$existing['period_start'], (int)$existing['period_end']);
    }

    public static function normalize(string $text): string
    {
        $text = trim($text);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $normalized = strtolower($ascii !== false ? $ascii : $text);
        return trim((string)preg_replace('/[^a-z0-9]+/', ' ', $normalized));
    }

    /** Domain-aware matching. Every semicolon/comma-delimited skill can match a course. */
    public static function expertiseMatch(string $expertise, string $subject): bool
    {
        $subject = self::normalize($subject);
        if ($subject === '') return false;
        foreach (preg_split('/[,;|]+/u', $expertise) ?: [] as $skill) {
            $skill = self::normalize($skill);
            if ($skill !== '' && (str_contains($subject, $skill) || str_contains($skill, $subject))) {
                return true;
            }
        }
        $words = array_values(array_filter(explode(' ', $subject), static fn(string $w): bool => strlen($w) >= 3 && !in_array($w, ['mon','hoc','co','so','va','cho','nhap','dai','cuong','tri'], true)));
        $haystack = ' ' . self::normalize($expertise) . ' ';
        foreach ($words as $word) {
            if (str_contains($haystack, ' ' . $word . ' ')) return true;
        }
        return false;
    }

    /** Recommendations prefer matching expertise, adjacent same-campus classes and balanced workload. */
    public static function score(array $teacher, array $schedules, array $candidate): int
    {
        $score = self::expertiseMatch((string)$teacher['expertise'], (string)$candidate['subject_name']) ? 70 : 0;
        $workload = 0;
        foreach ($schedules as $existing) {
            if ((int)$existing['teacher_id'] !== (int)$teacher['id']) continue;
            $workload += (int)$existing['period_end'] - (int)$existing['period_start'] + 1;
            if ((int)$existing['day_of_week'] !== (int)$candidate['day_of_week']) continue;
            $distance = min(abs((int)$existing['period_end'] - (int)$candidate['period_start']), abs((int)$candidate['period_end'] - (int)$existing['period_start']));
            if ((int)$existing['branch_id'] === (int)$candidate['branch_id']) {
                $score += $distance <= 2 ? 15 : 5;
            } else {
                $score -= 8;
            }
        }
        return $score + max(0, 15 - intdiv($workload, 2));
    }

    /** Complete availability check for one teacher and all their weekly classes. */
    public static function available(array $candidate, array $schedules, int $skipId = 0): bool
    {
        foreach ($schedules as $existing) {
            if ((int)$existing['id'] === $skipId) continue;
            if ((int)$existing['teacher_id'] !== (int)$candidate['teacher_id']) continue;
            if (self::teacherConflict($candidate, $existing)) return false;
        }
        return true;
    }
}
