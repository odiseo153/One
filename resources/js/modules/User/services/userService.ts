import { router, type InertiaFormProps } from '@inertiajs/react';
import type { UserFormData } from '@/modules/User/types/user';

const NONE = '__none__';

export const userService = {
    save(
        form: InertiaFormProps<UserFormData>,
        userId: number | null,
        onSuccess: () => void,
    ) {
        form.transform((values) => ({
            ...values,
            municipality_id:
                values.municipality_id === NONE
                    ? null
                    : Number(values.municipality_id),
            sector_id:
                values.sector_id === NONE ? null : Number(values.sector_id),
            password: values.password === '' ? undefined : values.password,
        }));

        if (userId) {
            form.patch(`/admin/users/${userId}`, { onSuccess });
            return;
        }

        form.post('/admin/users', { onSuccess });
    },

    deactivate(userId: number) {
        router.delete(`/admin/users/${userId}`, { preserveScroll: true });
    },

    filter(filters: Record<string, string | undefined>) {
        router.get('/admin/users', filters, {
            preserveState: true,
            replace: true,
        });
    },
};
