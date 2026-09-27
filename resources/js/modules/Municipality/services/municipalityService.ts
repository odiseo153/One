import { router, type InertiaFormProps } from '@inertiajs/react';
import type { MunicipalityFormData } from '@/modules/Municipality/types/municipality';

const NONE = '__none__';

export const municipalityService = {
    save(
        form: InertiaFormProps<MunicipalityFormData>,
        municipalityId: number | null,
        onSuccess: () => void,
    ) {
        form.transform((values) => ({
            ...values,
            province_id:
                values.province_id === NONE ? null : Number(values.province_id),
        }));

        if (municipalityId) {
            form.patch(`/admin/municipalities/${municipalityId}`, {
                onSuccess,
            });
            return;
        }

        form.post('/admin/municipalities', { onSuccess });
    },

    deactivate(municipalityId: number) {
        router.delete(`/admin/municipalities/${municipalityId}`, {
            preserveScroll: true,
        });
    },

    filter(filters: Record<string, string | undefined>) {
        router.get('/admin/municipalities', filters, {
            preserveState: true,
            replace: true,
        });
    },
};
