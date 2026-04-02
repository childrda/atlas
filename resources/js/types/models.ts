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
