import type { Paginated } from '@/shared/types/pagination';

export type MunicipalityRecord = {
    id: number;
    name: string;
    logo_url: string | null;
    domain: string | null;
    subdomain: string | null;
    province_id: number | null;
    status: string | null;
    registration_date: string | null;
    contracted_plan: string | null;
    province: { name: string } | null;
    deleted_at: string | null;
};

export type MunicipalityFormData = {
    name: string;
    province_id: string;
    domain: string;
    subdomain: string;
    logo_url: string;
    contracted_plan: string;
    status: string;
};

export type MunicipalitiesPageProps = {
    municipalities: Paginated<MunicipalityRecord>;
    provinces: { id: number; name: string }[];
    filters: { search?: string; status?: string };
};
