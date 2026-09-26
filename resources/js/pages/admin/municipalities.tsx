import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const NONE = '__none__';

type MunicipalityRecord = {
    id: number;
    name: string;
    logo_url: string | null;
    domain: string | null;
    subdomain: string | null;
    province_id: number | null;
    status: string | null;
    registration_date: string | null;
    contracted_plan: string | null;
    province: { name: string } | null;
    deleted_at: string | null;
};

type Props = {
    municipalities: {
        data: MunicipalityRecord[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    provinces: { id: number; name: string }[];
    filters: { search?: string; status?: string };
};

export default function Municipalities({
    municipalities,
    provinces,
    filters,
}: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<MunicipalityRecord | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const form = useForm({
        name: '',
        province_id: NONE,
        domain: '',
        subdomain: '',
        logo_url: '',
        contracted_plan: '',
        status: 'active',
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (municipality: MunicipalityRecord) => {
        setEditing(municipality);
        form.setData({
            name: municipality.name,
            province_id: municipality.province_id
                ? String(municipality.province_id)
                : NONE,
            domain: municipality.domain ?? '',
            subdomain: municipality.subdomain ?? '',
            logo_url: municipality.logo_url ?? '',
            contracted_plan: municipality.contracted_plan ?? '',
            status: municipality.status ?? 'active',
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((values) => ({
            ...values,
            province_id:
                values.province_id === NONE ? null : Number(values.province_id),
        }));

        if (editing) {
            form.patch(`/admin/municipalities/${editing.id}`, {
                onSuccess: () => setOpen(false),
            });
        } else {
            form.post('/admin/municipalities', {
                onSuccess: () => setOpen(false),
            });
        }
    };

    const destroy = (municipality: MunicipalityRecord) => {
        if (confirm(`¿Desactivar a ${municipality.name}?`)) {
            router.delete(`/admin/municipalities/${municipality.id}`, {
                preserveScroll: true,
            });
        }
    };

    const applyFilters = (patch: Record<string, string | undefined>) => {
        router.get(
            '/admin/municipalities',
            { ...filters, ...patch },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Municipios" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Municipios"
                        description="Gestiona los municipios del sistema"
                    />
                    <Button onClick={openCreate}>
                        <Plus />
                        Crear municipio
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            className="w-64 pl-8"
                            placeholder="Buscar nombre o dominio..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    applyFilters({
                                        search: search || undefined,
                                    });
                                }
                            }}
                        />
                    </div>
                    <Button
                        variant="outline"
                        onClick={() =>
                            applyFilters({ search: search || undefined })
                        }
                    >
                        Buscar
                    </Button>
                    <Select
                        value={filters.status ?? 'active'}
                        onValueChange={(value) =>
                            applyFilters({ status: value })
                        }
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Activos</SelectItem>
                            <SelectItem value="inactive">Inactivos</SelectItem>
                            <SelectItem value="all">Todos</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="border-sidebar-border/70 overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Provincia
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Dominio
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-sidebar-border/50 divide-y">
                            {municipalities.data.map((municipality) => (
                                <tr
                                    key={municipality.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {municipality.name}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {municipality.province?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {municipality.domain ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusBadge
                                            deletedAt={municipality.deleted_at}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    openEdit(municipality)
                                                }
                                            >
                                                <Pencil />
                                            </Button>
                                            {!municipality.deleted_at && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        destroy(municipality)
                                                    }
                                                >
                                                    <Trash2 />
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {municipalities.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="text-muted-foreground px-4 py-10 text-center"
                                    >
                                        No hay municipios.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={municipalities.links}
                    from={municipalities.from ?? undefined}
                    to={municipalities.to ?? undefined}
                    total={municipalities.total}
                />

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>
                                {editing
                                    ? 'Editar municipio'
                                    : 'Crear municipio'}
                            </DialogTitle>
                            <DialogDescription>
                                {editing
                                    ? 'Actualiza los datos del municipio.'
                                    : 'Completa los datos para registrar un nuevo municipio.'}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nombre</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Provincia</Label>
                                    <Select
                                        value={form.data.province_id}
                                        onValueChange={(value) =>
                                            form.setData('province_id', value)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                Sin provincia
                                            </SelectItem>
                                            {provinces.map((p) => (
                                                <SelectItem
                                                    key={p.id}
                                                    value={String(p.id)}
                                                >
                                                    {p.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.province_id}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="domain">Dominio</Label>
                                    <Input
                                        id="domain"
                                        value={form.data.domain}
                                        onChange={(e) =>
                                            form.setData(
                                                'domain',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError message={form.errors.domain} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="subdomain">
                                        Subdominio
                                    </Label>
                                    <Input
                                        id="subdomain"
                                        value={form.data.subdomain}
                                        onChange={(e) =>
                                            form.setData(
                                                'subdomain',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError
                                        message={form.errors.subdomain}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="logo_url">Logo URL</Label>
                                    <Input
                                        id="logo_url"
                                        value={form.data.logo_url}
                                        onChange={(e) =>
                                            form.setData(
                                                'logo_url',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError
                                        message={form.errors.logo_url}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="contracted_plan">
                                        Plan contratado
                                    </Label>
                                    <Input
                                        id="contracted_plan"
                                        value={form.data.contracted_plan}
                                        onChange={(e) =>
                                            form.setData(
                                                'contracted_plan',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError
                                        message={form.errors.contracted_plan}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Estado</Label>
                                    <Select
                                        value={form.data.status}
                                        onValueChange={(value) =>
                                            form.setData('status', value)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">
                                                Activo
                                            </SelectItem>
                                            <SelectItem value="inactive">
                                                Inactivo
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.status} />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {editing ? 'Guardar' : 'Crear'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}
