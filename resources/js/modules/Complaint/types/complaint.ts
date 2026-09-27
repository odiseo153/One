export type ComplaintFormData = {
    municipality_id: string;
    sector_id: string;
    category: string;
    description: string;
    latitude: string;
    longitude: string;
    address_text: string;
    photo: File | null;
    citizen_name: string;
    citizen_phone: string;
};

export type ComplaintAssignmentData = {
    assigned_user_id: string;
};

export type ComplaintStatusData = {
    status: string;
    note: string;
};

export type ComplaintFilters = Record<string, string | string[] | undefined>;
