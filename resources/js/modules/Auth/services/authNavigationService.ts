import { router } from '@inertiajs/react';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import { store as confirmPassword } from '@/routes/password/confirm';
import {
    email as forgotPassword,
    update as resetPassword,
} from '@/routes/password';
import { store as login } from '@/routes/login';
import { store as register } from '@/routes/register';
import {
    confirm as confirmTwoFactor,
    disable as disableTwoFactor,
    enable as enableTwoFactor,
    regenerateRecoveryCodes,
} from '@/routes/two-factor';
import { store as twoFactorChallenge } from '@/routes/two-factor/login';
import { send as verifyEmail } from '@/routes/verification';

export const authNavigationService = {
    verificationRoute: () => verifyEmail(),

    forms: {
        confirmPassword: () => confirmPassword.form(),
        forgotPassword: () => forgotPassword.form(),
        login: () => login.form(),
        register: () => register.form(),
        resetPassword: () => resetPassword.form(),
        twoFactorChallenge: () => twoFactorChallenge.form(),
        verifyEmail: () => verifyEmail.form(),
        confirmTwoFactor: () => confirmTwoFactor.form(),
        disableTwoFactor: () => disableTwoFactor.form(),
        enableTwoFactor: () => enableTwoFactor.form(),
        regenerateRecoveryCodes: () => regenerateRecoveryCodes.form(),
    },
    deletePasskey(id: number, onError: () => void) {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            onError,
        });
    },

    reload() {
        router.reload();
    },

    visit(url: string) {
        router.visit(url);
    },
};
