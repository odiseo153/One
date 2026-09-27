import type { Paginated } from '@/shared/types/pagination';

export type UserRecord = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string | null;
    municipality_id: number | null;
    sector_id: number | null;
    municipality: { name: string } | null;
    sector: { name: string } | null;
    deleted_at: string | null;
};

export type UserFormData = {
    name: string;
    email: string;
    password: string;
    municipality_id: string;
    sector_id: string;
    phone: string;
    status: string;
};

export type UsersPageProps = {
    users: Paginated<UserRecord>;
    municipalities: { id: number; name: string }[];
    sectors: { id: number; municipality_id: number; name: string }[];
    filters: { search?: string; status?: string };
};
