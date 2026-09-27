import { Head, useForm, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import DeleteUser from '@/modules/Settings/components/DeleteUser';
import { authNavigationService } from '@/modules/Auth/services/authNavigationService';
import { settingsService } from '@/modules/Settings/services/settingsService';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    SearchableSelect,
    type SearchableSelectOption,
} from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

const NONE = '__none__';

type MunicipalityOption = {
    id: number;
    name: string;
};

type SectorOption = {
    id: number;
    municipality_id: number;
    name: string;
};

type PageProps = {
    auth: Auth;
};

type ProfileUser = Auth['user'] & {
    phone?: string | null;
    municipality_id?: number | null;
    sector_id?: number | null;
};

export default function Profile({
    mustVerifyEmail,
    status,
    municipalities,
    sectors,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    municipalities: MunicipalityOption[];
    sectors: SectorOption[];
}) {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user as ProfileUser;

    const form = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        municipality_id: user.municipality_id
            ? String(user.municipality_id)
            : NONE,
        sector_id: user.sector_id ? String(user.sector_id) : NONE,
    });

    const municipalityOptions: SearchableSelectOption[] = useMemo(
        () => [
            { value: NONE, label: 'None' },
            ...municipalities.map((municipality) => ({
                value: String(municipality.id),
                label: municipality.name,
            })),
        ],
        [municipalities],
    );

    const sectorOptions: SearchableSelectOption[] = useMemo(
        () => [
            { value: NONE, label: 'None' },
            ...sectors
                .filter(
                    (sector) =>
                        form.data.municipality_id === NONE ||
                        sector.municipality_id ===
                            Number(form.data.municipality_id),
                )
                .map((sector) => ({
                    value: String(sector.id),
                    label: sector.name,
                })),
        ],
        [sectors, form.data.municipality_id],
    );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((values) => ({
            ...values,
            phone: values.phone === '' ? null : values.phone,
            municipality_id:
                values.municipality_id === NONE
                    ? null
                    : Number(values.municipality_id),
            sector_id:
                values.sector_id === NONE ? null : Number(values.sector_id),
        }));
        settingsService.updateProfile(form);
    };

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name, email address and location"
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                                autoComplete="name"
                                placeholder="Full name"
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.name}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                required
                                autoComplete="username"
                                placeholder="Email address"
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.email}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>

                            <Input
                                id="phone"
                                type="tel"
                                className="mt-1 block w-full"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                                autoComplete="tel"
                                placeholder="Phone number"
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.phone}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="municipality_id">
                                Municipality
                            </Label>

                            <SearchableSelect
                                id="municipality_id"
                                value={form.data.municipality_id}
                                onValueChange={(value) => {
                                    form.setData('municipality_id', value);
                                    form.setData('sector_id', NONE);
                                }}
                                options={municipalityOptions}
                                placeholder="Select a municipality"
                                emptyText="No municipality found."
                                className="mt-1"
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.municipality_id}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="sector_id">Sector</Label>

                            <SearchableSelect
                                id="sector_id"
                                value={form.data.sector_id}
                                onValueChange={(value) =>
                                    form.setData('sector_id', value)
                                }
                                options={sectorOptions}
                                placeholder="Select a sector"
                                emptyText="No sector found."
                                className="mt-1"
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.sector_id}
                            />
                        </div>
                    </div>

                    {mustVerifyEmail && user.email_verified_at === null && (
                        <div>
                            <p className="text-muted-foreground -mt-4 text-sm">
                                Your email address is unverified.{' '}
                                <Link
                                    href={authNavigationService.verificationRoute()}
                                    as="button"
                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Click here to re-send the verification
                                    email.
                                </Link>
                            </p>

                            {status === 'verification-link-sent' && (
                                <div className="mt-2 text-sm font-medium text-green-600">
                                    A new verification link has been sent to
                                    your email address.
                                </div>
                            )}
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <Button
                            disabled={form.processing}
                            data-test="update-profile-button"
                        >
                            Save
                        </Button>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
