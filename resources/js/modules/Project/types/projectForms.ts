import type { ProjectType } from '@/modules/Project/helpers/projectFormatting';

export type ProjectFormData = {
    municipality_id: string;
    sector_id: string;
    name: string;
    type: ProjectType;
    description: string;
    address_text: string;
    latitude: string;
    longitude: string;
    budget_assigned: string;
    start_date_planned: string;
    end_date_planned: string;
    contractor_name: string;
};

export type ProjectUpdateFormData = {
    update_date: string;
    progress_percentage: string;
    description: string;
    status: string;
    budget_spent: string;
    taken_at: string;
    photos: File[];
    captions: string[];
};

export type ProjectAssignmentFormData = {
    user_id: string;
    role_in_project: string;
};

export type ProjectStatusFormData = { status: string; note: string };
export type ProjectMilestoneFormData = { name: string; planned_date: string };
