export interface School {
    id: number;
    name: string;
    slug: string;
}

export interface AcademicYear {
    id: number;
    year: string;
}

export interface Subject {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface Student {
    id: number;
    firstname: string;
    lastname: string;
    email?: string;
    group_id: number;
}

export interface Lesson {
    id: number;
    name: string;
    subject_id: number;
    subject?: Subject;
    user?: User;
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