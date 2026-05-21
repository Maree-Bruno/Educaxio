export type User = {
    id: number;
    name: string;
    email: string;
    picture?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type SchoolRole = {
    id: number;
    name: string;
    slug: string;
    role: 'admin' | 'teacher';
};

export type Auth = {
    user: User;
    schoolRoles: SchoolRole[] | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
