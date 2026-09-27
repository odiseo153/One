import type { RegistrationStatus } from '@/modules/Business/components/SectorBusinessMap';
import type {
    DocumentType,
    PropertyStatus,
} from '@/modules/Business/constants/businessOptions';

export type BusinessCategoryOption = {
    id: number;
    code: string;
    name: string;
};

export type BusinessEmployeeFormData = {
    id?: number;
    first_name: string;
    last_name: string;
    document_type: DocumentType;
    document_number: string;
    salary: string;
};

export type BusinessFormData = {
    municipality_id: string;
    sector_id: string;
    name: string;
    category: string;
    latitude: string;
    longitude: string;
    address_text: string;
    registration_status: RegistrationStatus;
    is_registered: boolean;
    property_status: PropertyStatus;
    document_type: DocumentType;
    document_number: string;
    rnc: string;
    primary_activity: string;
    secondary_activity: string;
    photo: File | null;
    detected_at: string;
    last_verified_at: string;
    inspector_id: string;
    employees: BusinessEmployeeFormData[];
};
