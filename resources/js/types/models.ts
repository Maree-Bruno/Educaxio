export interface School {
    id: number;
    name: string;
    slug: string;
}

export interface UserSummary {
    id: number;
    name: string;
    email: string;
}

export interface AcademicYear {
    id: number;
    year: string;
}

export interface Subject {
    id: number;
    name: string;
}

export interface Student {
    id: number;
    slug: string;
    school_id: number;
    school?: Pick<School, 'id' | 'name'>;
    firstname: string;
    lastname: string;
    email?: string | null;
    groups?: (Pick<Group, 'id' | 'grade' | 'name' | 'slug' | 'school_id'> & {
        school: Pick<School, 'id' | 'name'>;
    })[];
}

export interface Lesson {
    id: number;
    name: string;
    subject_id: number;
    group_id: number;
    subject?: Subject;
    group?: {
        id: number;
        grade: string;
        name: string;
        slug: string;
        school_id: number;
        school: Pick<School, 'id' | 'name'>;
    };
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface Schedule {
    id: number;
    user_id: number;
    school_id: number;
    academic_year_id: number;
    name: string | null;
    school?: Pick<School, 'id' | 'name'>;
    academic_year?: AcademicYear;
    slots?: ScheduleSlot[];
    slots_count?: number;
}

export interface ScheduleSlot {
    id: number;
    schedule_id: number;
    position: number;
    label: string;
    classroom: string | null;
    type: 'slot' | 'lunch';
}

export type SlotRow = Pick<ScheduleSlot, 'id' | 'position' | 'label' | 'type'>;

export interface ScheduleEntry {
    id: number;
    lesson_id: number;
    grade: string;
    subject: string;
    room: string | null;
    school: string;
}

export interface LessonOption {
    id: number;
    group_id: number;
    subject_id: number;
    group: {
        id: number;
        grade: string;
        name: string;
        school_id: number;
        school: { id: number; name: string };
    };
    subject: { id: number; name: string };
}

export interface Group {
    id: number;
    name: string;
    grade: string;
    slug: string;
    school_id: number;
    school: School;
    academic_year_id: number;
    academic_year: AcademicYear;
    students_count: number;
    lessons: Lesson[];
}
