import { KeyRound } from 'lucide-react';

export function PasskeyEmptyState() {
    return (
        <div className="p-8 text-center">
            <div className="bg-muted mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl">
                <KeyRound className="text-muted-foreground h-7 w-7" />
            </div>
            <p className="font-medium">No passkeys yet</p>
            <p className="text-muted-foreground mt-1 text-sm">
                Add a passkey to sign in without a password
            </p>
        </div>
    );
}
