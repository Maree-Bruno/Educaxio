<?php

namespace App\Enums;

enum Attendance_type
{
    public const EXCLUDED = 'Excluded';
    public const LATE = 'Late';
    public const ABSENT = 'Absent';

    public static function values(): array
    {
        return [
            self::ABSENT,
            self::LATE,
            self::EXCLUDED,
        ];
    }
}
