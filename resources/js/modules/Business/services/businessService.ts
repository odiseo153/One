import { router, type InertiaFormProps } from '@inertiajs/react';
import type {
    BusinessMapRecord,
    DiscoveredPlaceRecord,
    RegistrationStatus,
} from '@/modules/Business/components/SectorBusinessMap';
import { normalizeDocumentNumber } from '@/modules/Business/helpers/documentNumber';
import type {
    BusinessCategoryOption,
    BusinessFormData,
} from '@/modules/Business/types/businessForm';

function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export const businessService = {
    async categories(): Promise<BusinessCategoryOption[]> {
        const response = await fetch('/admin/business-categories', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return [];
        }

        const payload = (await response.json()) as {
            data: BusinessCategoryOption[];
        };

        return payload.data;
    },

    filterMap(filter: Record<string, string>) {
        router.get(
            '/admin/sector-map',
            { filter },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    },

    async updateStatus(
        businessId: number,
        registrationStatus: RegistrationStatus,
    ): Promise<BusinessMapRecord | null> {
        const response = await fetch(
            `/admin/sector-map/businesses/${businessId}/status`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    registration_status: registrationStatus,
                }),
            },
        );

        if (!response.ok) {
            return null;
        }

        const payload = (await response.json()) as { data: BusinessMapRecord };
        return payload.data;
    },

    async discoverPlaces(params: URLSearchParams) {
        const response = await fetch(`/admin/sector-map/places?${params}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return [];
        }

        const payload = (await response.json()) as {
            places: DiscoveredPlaceRecord[];
        };
        return payload.places;
    },

    save(
        form: InertiaFormProps<BusinessFormData>,
        businessId: number | null,
        onSuccess: () => void,
    ) {
        form.transform((values) => ({
            ...values,
            ...(businessId ? { _method: 'patch' } : {}),
            municipality_id: values.municipality_id
                ? Number(values.municipality_id)
                : null,
            sector_id:
                values.sector_id === 'none' ? null : Number(values.sector_id),
            inspector_id:
                values.inspector_id === 'none'
                    ? null
                    : Number(values.inspector_id),
            latitude: Number(values.latitude),
            longitude: Number(values.longitude),
            category:
                !values.category || values.category === 'none'
                    ? null
                    : values.category,
            address_text: values.address_text || null,
            registration_status: values.is_registered
                ? 'registered'
                : 'unregistered',
            document_type: values.document_type || null,
            document_number: normalizeDocumentNumber(
                values.document_type,
                values.document_number,
            ),
            rnc: values.rnc || null,
            primary_activity: values.primary_activity || null,
            secondary_activity: values.secondary_activity || null,
            primary_ciiu_id: null,
            secondary_ciiu_id: null,
            employees: values.employees.map((employee) => ({
                ...employee,
                document_number: normalizeDocumentNumber(
                    employee.document_type,
                    employee.document_number,
                ),
            })),
            photo: values.photo ?? undefined,
            detected_at: values.detected_at || null,
            last_verified_at: values.last_verified_at || null,
        }));

        form.post(
            businessId
                ? `/admin/sector-map/businesses/${businessId}`
                : '/admin/sector-map/businesses',
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess,
            },
        );
    },

    openProject(projectId: number) {
        router.visit(`/admin/projects/${projectId}`);
    },

    list(search: string, sort?: string) {
        router.get(
            '/admin/registered-businesses',
            {
                ...(search ? { filter: { search } } : {}),
                ...(sort ? { sort } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    },
};
