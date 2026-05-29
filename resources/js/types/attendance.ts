export type AttendanceStatus = 'Absent' | 'Late' | 'Excluded';

export const ATTENDANCE_STATUS_COLORS: Record<AttendanceStatus | '', [string, string]> = {
    '':         ['bg-green-100',  'text-green-800'],
    Absent:     ['bg-red-100',    'text-red-800'],
    Late:       ['bg-yellow-100', 'text-yellow-800'],
    Excluded:   ['bg-gray-100',   'text-gray-700'],
};

export const ATTENDANCE_STATUS_LABELS: Record<AttendanceStatus, string> = {
    Absent:   'Absent',
    Late:     'Arrivée tardive',
    Excluded: 'Exclu',
};

export function attendanceStatusClasses(status: AttendanceStatus | null): string {
    const [bg, text] = ATTENDANCE_STATUS_COLORS[status ?? ''];

    return `${bg} ${text}`;
}
