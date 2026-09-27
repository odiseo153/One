import Heading from '@/components/heading';
import { PasskeyEmptyState } from '@/modules/Auth/components/PasskeyEmptyState';
import PasskeyItem from '@/modules/Auth/components/PasskeyItem';
import PasskeyRegistration from '@/modules/Auth/components/PasskeyRegister';
import { authNavigationService } from '@/modules/Auth/services/authNavigationService';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

export default function ManagePasskeys(props: Props) {
    const passkeys = props.passkeys ?? [];

    const handleDelete = (id: number, onError: () => void) => {
        authNavigationService.deletePasskey(id, onError);
    };

    const handleRegisterSuccess = () => {
        authNavigationService.reload();
    };

    if (!(props.canManagePasskeys ?? false)) {
        return null;
    }

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Passkeys"
                description="Manage your passkeys for passwordless sign-in"
            />

            <div className="border-border overflow-hidden rounded-lg border">
                {passkeys.length > 0 ? (
                    passkeys.map((passkey) => (
                        <PasskeyItem
                            key={passkey.id}
                            passkey={passkey}
                            onDelete={handleDelete}
                        />
                    ))
                ) : (
                    <PasskeyEmptyState />
                )}
            </div>

            <PasskeyRegistration onSuccess={handleRegisterSuccess} />
        </div>
    );
}
