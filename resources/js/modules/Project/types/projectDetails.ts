export type SectorDetail = {
    id: number;
    name: string;
    geojson_polygon: GeoJsonPolygon | null;
};

export type GeoJsonPolygon = {
    type: 'Polygon';
    coordinates: number[][][];
};

export type PhotoRecord = {
    id: number;
    photo_url: string;
    caption: string | null;
    taken_at: string | null;
    uploader: { id: number; name: string } | null;
};

export type UpdateRecord = {
    id: number;
    update_date: string;
    progress_percentage_at_update: number;
    description: string | null;
    status_at_update: string | null;
    budget_spent_at_update: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
    photos: PhotoRecord[];
};

export type ProjectUserRecord = {
    id: number;
    role_in_project: string;
    assigned_at: string | null;
    user: { id: number; name: string } | null;
};

export type MilestoneRecord = {
    id: number;
    name: string;
    order: number;
    planned_date: string | null;
    completed_date: string | null;
    status: string;
};

export type ProjectDetail = {
    id: number;
    name: string;
    type: string;
    status: string;
    description: string | null;
    latitude: number | null;
    longitude: number | null;
    address_text: string | null;
    budget_assigned: string | null;
    budget_executed: string | null;
    progress_percentage: number;
    contractor_name: string | null;
    start_date_planned: string | null;
    end_date_planned: string | null;
    start_date_real: string | null;
    end_date_real: string | null;
    created_at: string;
    municipality: { id: number; name: string };
    sector: SectorDetail | null;
    creator: { id: number; name: string } | null;
    project_users: ProjectUserRecord[];
    updates: UpdateRecord[];
    photos: PhotoRecord[];
    milestones: MilestoneRecord[];
};
