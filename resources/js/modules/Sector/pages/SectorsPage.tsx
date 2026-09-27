import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import { sectorService } from '@/modules/Sector/services/sectorService';
import type {
    SectorFormData,
    SectorRecord,
    SectorsPageProps,
} from '@/modules/Sector/types/sector';
import { AdminListFilters } from '@/shared/components/AdminListFilters';

export default function Sectors({
    sectors,
    municipalities,
    filters,
}: SectorsPageProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<SectorRecord | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const form = useForm<SectorFormData>({
        name: '',
        municipality_id: '',
        geojson_polygon: '',
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (sector: SectorRecord) => {
        setEditing(sector);
        form.setData({
            name: sector.name,
            municipality_id: String(sector.municipality_id),
            geojson_polygon: sector.geojson_polygon
                ? JSON.stringify(sector.geojson_polygon, null, 2)
                : '',
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        sectorService.save(form, editing?.id ?? null, () => setOpen(false));
    };

    const destroy = (sector: SectorRecord) => {
        if (confirm(`¿Desactivar a ${sector.name}?`)) {
            sectorService.deactivate(sector.id);
        }
    };

    const applyFilters = (patch: Record<string, string | undefined>) => {
        sectorService.filter({ ...filters, ...patch });
    };

    return (
        <>
            <Head title="Sectores" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Sectores"
                        description="Gestiona los sectores del sistema"
                    />
                    <Button onClick={openCreate}>
                        <Plus />
                        Crear sector
                    </Button>
                </div>

                <AdminListFilters
                    search={search}
                    status={filters.status ?? 'active'}
                    placeholder="Buscar nombre..."
                    onSearchChange={setSearch}
                    onSearch={() =>
                        applyFilters({ search: search || undefined })
                    }
                    onStatusChange={(status) => applyFilters({ status })}
                />

                <div className="border-sidebar-border/70 overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Municipio
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
                            {sectors.data.map((sector) => (
                                <tr
                                    key={sector.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {sector.name}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {sector.municipality?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusBadge
                                            deletedAt={sector.deleted_at}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => openEdit(sector)}
                                            >
                                                <Pencil />
                                            </Button>
                                            {!sector.deleted_at && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        destroy(sector)
                                                    }
                                                >
                                                    <Trash2 />
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {sectors.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="text-muted-foreground px-4 py-10 text-center"
                                    >
                                        No hay sectores.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={sectors.links}
                    from={sectors.from ?? undefined}
                    to={sectors.to ?? undefined}
                    total={sectors.total}
                />

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>
                                {editing ? 'Editar sector' : 'Crear sector'}
                            </DialogTitle>
                            <DialogDescription>
                                {editing
                                    ? 'Actualiza los datos del sector.'
                                    : 'Completa los datos para registrar un nuevo sector.'}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={submit} className="space-y-4">
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
                                <Label>Municipio</Label>
                                <Select
                                    value={form.data.municipality_id}
                                    onValueChange={(value) =>
                                        form.setData('municipality_id', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Selecciona un municipio" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {municipalities.map((m) => (
                                            <SelectItem
                                                key={m.id}
                                                value={String(m.id)}
                                            >
                                                {m.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.municipality_id}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="geojson_polygon">
                                    GeoJSON (opcional)
                                </Label>
                                <Textarea
                                    id="geojson_polygon"
                                    rows={5}
                                    value={form.data.geojson_polygon}
                                    onChange={(e) =>
                                        form.setData(
                                            'geojson_polygon',
                                            e.target.value,
                                        )
                                    }
                                    placeholder='{"type":"Polygon","coordinates":[...]}'
                                    className="font-mono text-xs"
                                />
                                <InputError
                                    message={form.errors.geojson_polygon}
                                />
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
