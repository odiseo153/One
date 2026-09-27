import { useEffect, useState } from 'react';
import { businessService } from '@/modules/Business/services/businessService';
import type { BusinessCategoryOption } from '@/modules/Business/types/businessForm';

export function useBusinessCategories(enabled: boolean) {
    const [categories, setCategories] = useState<BusinessCategoryOption[]>([]);
    const [loading, setLoading] = useState(false);
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        if (!enabled || loaded) {
            return;
        }

        setLoading(true);

        void businessService
            .categories()
            .then(setCategories)
            .catch(() => setCategories([]))
            .finally(() => {
                setLoading(false);
                setLoaded(true);
            });
    }, [enabled, loaded]);

    return { categories, loading };
}
