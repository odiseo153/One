import type { Paginated } from '@/shared/types/pagination';

export type SectorRecord = {
    id: number;
    name: string;
    municipality_id: number;
    geojson_polygon: unknown;
    municipality: { name: string } | null;
    deleted_at: string | null;
};

export type SectorFormData = {
    name: string;
    municipality_id: string;
    geojson_polygon: string;
};

export type SectorsPageProps = {
    sectors: Paginated<SectorRecord>;
    municipalities: { id: number; name: string }[];
    filters: { search?: string; status?: string };
};
