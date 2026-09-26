import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { Input } from '@/components/ui/input';

type RegistrationStatus =
    | 'registered'
    | 'unregistered'
    | 'pending_verification';

type BusinessRecord = {
    id: number;
    name: string;
    category: string | null;
    rnc: string | null;
    address_text: string | null;
    registration_status: RegistrationStatus;
    detected_at: string | null;
    last_verified_at: string | null;
    sector: { id: number; name: string } | null;
    municipality: {
        id: number;
        name: string;
        province: { id: number; name: string } | null;
    } | null;
    inspector: { id: number; name: string } | null;
};

type Props = {
    businesses: {
        data: BusinessRecord[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        filter?: {
            search?: string;
        };
        sort?: string;
    };
};

const statusLabels: Record<RegistrationStatus, string> = {
    registered: 'Registrado',
    unregistered: 'No registrado',
    pending_verification: 'Pendiente',
};

const sortableColumns = new Set([
    'name',
    'category',
    'registration_status',
    'detected_at',
    'last_verified_at',
]);

export default function RegisteredBusinesses({ businesses, filters }: Props) {
    const currentSort =
        typeof filters.sort === 'string' ? filters.sort : undefined;
    const [search, setSearch] = useState(filters.filter?.search ?? '');

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                '/admin/registered-businesses',
                {
                    ...(search
                        ? { filter: { search } }
                        : {}),
                    ...(currentSort ? { sort: currentSort } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 500);

        return () => window.clearTimeout(timeout);
    }, [currentSort, search]);

    const sortBy = (column: string) => {
        if (!sortableColumns.has(column)) {
            return;
        }

        const nextSort = currentSort === column ? `-${column}` : column;

        router.get(
            '/admin/registered-businesses',
            {
                ...(search ? { filter: { search } } : {}),
                sort: nextSort,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title="Negocios" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Negocios"
                    description="Todos los locales detectados"
                />

                <div className="relative max-w-xl">
                    <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                    <Input
                        className="pl-8"
                        placeholder="Buscar por nombre, categoria, RNC, provincia, municipio, zona, inspector, estado o direccion..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>

                <div className="border-sidebar-border/70 overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground text-left">
                            <tr>
                                <Header
                                    label="Nombre"
                                    column="name"
                                    sort={currentSort}
                                    onSort={sortBy}
                                />
                                <Header
                                    label="Categoria"
                                    column="category"
                                    sort={currentSort}
                                    onSort={sortBy}
                                />
                                <th className="px-4 py-3 font-medium">RNC</th>
                                <th className="px-4 py-3 font-medium">
                                    Provincia
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Municipio
                                </th>
                                <th className="px-4 py-3 font-medium">Zona</th>
                                <th className="px-4 py-3 font-medium">
                                    Inspector
                                </th>
                                <Header
                                    label="Estado"
                                    column="registration_status"
                                    sort={currentSort}
                                    onSort={sortBy}
                                />
                                <Header
                                    label="Detectado"
                                    column="detected_at"
                                    sort={currentSort}
                                    onSort={sortBy}
                                />
                                <Header
                                    label="Verificado"
                                    column="last_verified_at"
                                    sort={currentSort}
                                    onSort={sortBy}
                                />
                            </tr>
                        </thead>
                        <tbody className="divide-sidebar-border/50 divide-y">
                            {businesses.data.map((business) => (
                                <tr
                                    key={business.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {business.name}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.category ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.rnc ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.municipality?.province
                                            ?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.municipality?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.sector?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.inspector?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {statusLabels[business.registration_status]}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.detected_at ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {business.last_verified_at ?? '—'}
                                    </td>
                                </tr>
                            ))}
                            {businesses.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={10}
                                        className="text-muted-foreground px-4 py-10 text-center"
                                    >
                                        No hay negocios.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={businesses.links}
                    from={businesses.from ?? undefined}
                    to={businesses.to ?? undefined}
                    total={businesses.total}
                />
            </div>
        </>
    );
}

function Header({
    label,
    column,
    sort,
    onSort,
}: {
    label: string;
    column: string;
    sort?: string;
    onSort: (column: string) => void;
}) {
    const active = sort === column || sort === `-${column}`;
    const descending = sort === `-${column}`;

    return (
        <th className="px-4 py-3 font-medium">
            <button
                type="button"
                className="hover:text-foreground inline-flex items-center gap-1"
                onClick={() => onSort(column)}
            >
                {label}
                {active ? (
                    descending ? (
                        <ArrowDown className="size-3.5" />
                    ) : (
                        <ArrowUp className="size-3.5" />
                    )
                ) : null}
            </button>
        </th>
    );
}
