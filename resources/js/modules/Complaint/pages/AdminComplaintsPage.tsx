import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Eye, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { complaintService } from '@/modules/Complaint/services/complaintService';
import { complaintStatusClass } from '@/modules/Complaint/helpers/complaintStatus';
import { ColumnMultiSelectFilter } from '@/shared/components/ColumnMultiSelectFilter';

type Option = { value: string; label: string };
type SectorOption = { id: number; name: string };

type ComplaintRecord = {
    id: number;
    tracking_code: string;
    category: string;
    description: string;
    sector: { name: string } | null;
    assigned_user: { name: string } | null;
    status: string;
    created_at: string;
};

type Stats = {
    total: number;
    unassigned: number;
    by_status: Record<string, number>;
    by_category: Record<string, number>;
};

type Props = {
    complaints: {
        data: ComplaintRecord[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    sectors: SectorOption[];
    categories: Option[];
    statuses: Option[];
    filters: {
        search?: string;
        status?: string[];
        category?: string[];
        sector_id?: string[];
        assigned?: string[];
        from?: string;
        to?: string;
    };
    stats: Stats;
};

function statusLabel(status: string, statuses: Option[]) {
    return statuses.find((item) => item.value === status)?.label ?? status;
}

function categoryLabel(category: string, categories: Option[]) {
    return (
        categories.find((item) => item.value === category)?.label ?? category
    );
}

type FilterKey = 'status' | 'category' | 'sector_id' | 'assigned';

export default function AdminComplaints({
    complaints,
    sectors,
    categories,
    statuses,
    filters,
    stats,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const skipFirst = useRef(true);

    const applyFilters = (
        patch: Record<string, string | string[] | undefined>,
    ) => {
        complaintService.filterAdmin({ ...filters, ...patch });
    };

    useEffect(() => {
        if (skipFirst.current) {
            skipFirst.current = false;
            return;
        }
        const timeout = setTimeout(() => {
            applyFilters({ search: search || undefined });
        }, 500);
        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const toggleFilter = (key: FilterKey, value: string) => {
        const current = filters[key] ?? [];
        const next = current.includes(value)
            ? current.filter((item) => item !== value)
            : [...current, value];
        applyFilters({ [key]: next.length > 0 ? next : undefined });
    };

    const maxCategory = Math.max(
        ...categories.map((category) => stats.by_category[category.value] ?? 0),
        1,
    );

    const summaryCards = [
        { label: 'Total', value: stats.total, tone: 'text-foreground' },
        {
            label: 'Recibidas',
            value: stats.by_status.received ?? 0,
            tone: 'text-sky-700 dark:text-sky-400',
        },
        {
            label: 'En proceso',
            value: stats.by_status.in_progress ?? 0,
            tone: 'text-amber-700 dark:text-amber-400',
        },
        {
            label: 'Resueltas',
            value: stats.by_status.resolved ?? 0,
            tone: 'text-emerald-700 dark:text-emerald-400',
        },
    ];

    return (
        <>
            <Head title="Quejas" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Quejas ciudadanas"
                    description="Gestiona, asigna y da seguimiento a las quejas recibidas."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {summaryCards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-normal">
                                    {card.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p
                                    className={`text-3xl font-semibold ${card.tone}`}
                                >
                                    {card.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {stats.unassigned > 0 && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-400">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <div>
                            <p className="font-medium">
                                {stats.unassigned} quejas sin asignar
                            </p>
                            <p className="text-amber-700/80 dark:text-amber-400/80">
                                Se recomienda asignar un responsable para dar
                                seguimiento.
                            </p>
                        </div>
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Quejas por categoría</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {categories.map((category) => {
                            const count =
                                stats.by_category[category.value] ?? 0;
                            const width = Math.round(
                                (count / maxCategory) * 100,
                            );

                            return (
                                <div key={category.value} className="space-y-1">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-medium">
                                            {category.label}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {count}
                                        </span>
                                    </div>
                                    <div className="bg-muted h-2 w-full overflow-hidden rounded-full">
                                        <div
                                            className="bg-primary h-full rounded-full"
                                            style={{ width: `${width}%` }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <div className="flex flex-wrap items-end gap-2">
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            className="w-64 pl-8"
                            placeholder="Buscar, código, ciudadano..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <div className="flex items-end gap-2">
                        <div className="space-y-1">
                            <Label className="text-xs">Desde</Label>
                            <Input
                                type="date"
                                value={filters.from ?? ''}
                                onChange={(e) =>
                                    applyFilters({
                                        from: e.target.value || undefined,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs">Hasta</Label>
                            <Input
                                type="date"
                                value={filters.to ?? ''}
                                onChange={(e) =>
                                    applyFilters({
                                        to: e.target.value || undefined,
                                    })
                                }
                            />
                        </div>
                    </div>
                </div>

                <div className="border-sidebar-border/70 overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    Código
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    <ColumnMultiSelectFilter
                                        label="Categoría"
                                        options={categories}
                                        selected={filters.category ?? []}
                                        onToggle={(value) =>
                                            toggleFilter('category', value)
                                        }
                                    />
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Descripción
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    <ColumnMultiSelectFilter
                                        label="Sector"
                                        options={sectors.map((sector) => ({
                                            value: String(sector.id),
                                            label: sector.name,
                                        }))}
                                        selected={filters.sector_id ?? []}
                                        onToggle={(value) =>
                                            toggleFilter('sector_id', value)
                                        }
                                    />
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    <ColumnMultiSelectFilter
                                        label="Asignada a"
                                        options={[
                                            {
                                                value: 'assigned',
                                                label: 'Asignadas',
                                            },
                                            {
                                                value: 'unassigned',
                                                label: 'Sin asignar',
                                            },
                                        ]}
                                        selected={filters.assigned ?? []}
                                        onToggle={(value) =>
                                            toggleFilter('assigned', value)
                                        }
                                    />
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    <ColumnMultiSelectFilter
                                        label="Estado"
                                        options={statuses}
                                        selected={filters.status ?? []}
                                        onToggle={(value) =>
                                            toggleFilter('status', value)
                                        }
                                    />
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-sidebar-border/50 divide-y">
                            {complaints.data.map((complaint) => (
                                <tr
                                    key={complaint.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-mono text-xs font-medium">
                                        {complaint.tracking_code}
                                    </td>
                                    <td className="px-4 py-3">
                                        {categoryLabel(
                                            complaint.category,
                                            categories,
                                        )}
                                    </td>
                                    <td className="text-muted-foreground max-w-[220px] truncate px-4 py-3">
                                        {complaint.description}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {complaint.sector?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {complaint.assigned_user?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            className={complaintStatusClass(
                                                complaint.status,
                                            )}
                                        >
                                            {statusLabel(
                                                complaint.status,
                                                statuses,
                                            )}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={`/admin/complaints/${complaint.id}`}
                                                >
                                                    <Eye />
                                                </Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {complaints.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="text-muted-foreground px-4 py-10 text-center"
                                    >
                                        No hay quejas.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={complaints.links}
                    from={complaints.from ?? undefined}
                    to={complaints.to ?? undefined}
                    total={complaints.total}
                />
            </div>
        </>
    );
}
