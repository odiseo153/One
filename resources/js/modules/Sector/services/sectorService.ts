import { router, type InertiaFormProps } from '@inertiajs/react';
import type { SectorFormData } from '@/modules/Sector/types/sector';

export const sectorService = {
    save(
        form: InertiaFormProps<SectorFormData>,
        sectorId: number | null,
        onSuccess: () => void,
    ) {
        form.transform((values) => ({
            ...values,
            municipality_id: Number(values.municipality_id),
            geojson_polygon:
                values.geojson_polygon === ''
                    ? undefined
                    : values.geojson_polygon,
        }));

        if (sectorId) {
            form.patch(`/admin/sectors/${sectorId}`, { onSuccess });
            return;
        }

        form.post('/admin/sectors', { onSuccess });
    },

    deactivate(sectorId: number) {
        router.delete(`/admin/sectors/${sectorId}`, {
            preserveScroll: true,
        });
    },

    filter(filters: Record<string, string | undefined>) {
        router.get('/admin/sectors', filters, {
            preserveState: true,
            replace: true,
        });
    },
};
