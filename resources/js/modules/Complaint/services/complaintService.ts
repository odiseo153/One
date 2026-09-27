import { router, type InertiaFormProps } from '@inertiajs/react';
import type {
    ComplaintAssignmentData,
    ComplaintFilters,
    ComplaintFormData,
    ComplaintStatusData,
} from '@/modules/Complaint/types/complaint';

const NONE = '__none__';
const EMPTY = '__empty__';

export const complaintService = {
    create(form: InertiaFormProps<ComplaintFormData>, onSuccess: () => void) {
        form.transform((values) => ({
            ...values,
            municipality_id:
                values.municipality_id === NONE
                    ? null
                    : Number(values.municipality_id),
            sector_id:
                values.sector_id === NONE ? null : Number(values.sector_id),
            latitude: values.latitude === '' ? null : Number(values.latitude),
            longitude:
                values.longitude === '' ? null : Number(values.longitude),
            photo: values.photo ?? undefined,
        }));
        form.post('/quejas', { forceFormData: true, onSuccess });
    },

    track(trackingCode: string) {
        router.get(`/quejas/${encodeURIComponent(trackingCode)}`);
    },

    filterAdmin(filters: ComplaintFilters) {
        router.get('/admin/complaints', filters, {
            preserveState: true,
            replace: true,
        });
    },

    openAdminList() {
        router.get('/admin/complaints');
    },

    assign(
        form: InertiaFormProps<ComplaintAssignmentData>,
        complaintId: number,
    ) {
        form.transform((values) => ({
            assigned_user_id:
                values.assigned_user_id === EMPTY
                    ? null
                    : Number(values.assigned_user_id),
        }));
        form.patch(`/admin/complaints/${complaintId}/assign`, {
            preserveScroll: true,
        });
    },

    updateStatus(
        form: InertiaFormProps<ComplaintStatusData>,
        complaintId: number,
    ) {
        form.patch(`/admin/complaints/${complaintId}/status`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    },
};
