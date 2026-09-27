import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';

export const settingsService = {
    forms: {
        deleteProfile: () => ProfileController.destroy.form(),
        updatePassword: () => SecurityController.update.form(),
    },

    updateProfile(form: {
        patch: (url: string, options: { preserveScroll: boolean }) => void;
    }) {
        form.patch(ProfileController.update.url(), {
            preserveScroll: true,
        });
    },
};
