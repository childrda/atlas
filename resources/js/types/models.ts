export interface District {
    id: string;
    name: string;
    primary_color: string;
    accent_color: string;
}

export interface School {
    id: string;
    name: string;
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
    roles: string[];
    district: District;
    school: School | null;
}

export interface Classroom {
    id: string;
    name: string;
    subject: string | null;
    grade_level: string | null;
    join_code: string;
    students_count?: number;
    students?: User[];
    spaces?: LearningSpace[];
}

export interface LearningSpace {
    id: string;
    title: string;
    description: string | null;
    subject: string | null;
    grade_level: string | null;
    join_code: string;
    bridger_tone: string;
    is_published: boolean;
    is_archived: boolean;
    goals: string[];
    sessions_count?: number;
    classroom_id?: string | null;
    system_prompt?: string | null;
    language?: string;
    max_messages?: number | null;
    classroom?: { id: string; name: string } | null;
    teacher?: { id: string; name: string };
}

export interface StudentSession {
    id: string;
    status: string;
    message_count: number;
    started_at: string;
    student?: { id: string; name: string };
}
