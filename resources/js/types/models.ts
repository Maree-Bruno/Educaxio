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

export interface Student {
    id: number;
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
    subject?: Subject;
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
