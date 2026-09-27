import { Head, Link } from '@inertiajs/react';
import { Building2, Eye, HardHat, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    formatDate,
    formatMoney,
    projectStatusLabel,
    projectTypeLabel,
    progressColorClass,
    statusBadgeClass,
} from '@/modules/Project/helpers/projectFormatting';
import { ColumnMultiSelectFilter } from '@/shared/components/ColumnMultiSelectFilter';
import { projectService } from '@/modules/Project/services/projectService';

type Option = { value: string; label: string };
type SectorOption = { id: number; name: string };
type MunicipalityOption = { id: number; name: string };

type ProjectRecord = {
    id: number;
    name: string;
    type: string;
    status: string;
    progress_percentage: number;
    budget_assigned: string | null;
    budget_executed: string | null;
    start_date_planned: string | null;
    end_date_planned: string | null;
    contractor_name: string | null;
    updates_count: number;
    sector: { id: number; name: string } | null;
    municipality: { id: number; name: string } | null;
    description: string | null;
};

type Stats = {
    total: number;
    assigned: number;
    executed: number;
    by_status: Record<string, number>;
};

type Props = {
    projects: {
        data: ProjectRecord[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    sectors: SectorOption[];
    types: Option[];
    statuses: Option[];
    municipalities: MunicipalityOption[];
    filters: {
        search?: string;
        type?: string[];
        status?: string[];
        sector_id?: string[];
        from?: string;
        to?: string;
        municipality_id?: string;
    };
    stats: Stats;
};

type FilterKey = 'type' | 'status' | 'sector_id';

export default function AdminProjects({
    projects,
    sectors,
    types,
    statuses,
    municipalities,
    filters,
    stats,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const skipFirst = useRef(true);

    const applyFilters = (
        patch: Record<string, string | string[] | undefined>,
    ) => {
        projectService.filter({ ...filters, ...patch });
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

    const hasActiveMunicipalityFilter =
        municipalities.length > 1 || filters.municipality_id;

    const summaryCards = [
        {
            label: 'Total de obras',
            value: String(stats.total),
            icon: HardHat,
        },
        {
            label: 'En ejecución',
            value: String(stats.by_status.in_progress ?? 0),
            icon: Building2,
        },
        {
            label: 'Completadas',
            value: String(stats.by_status.completed ?? 0),
            icon: Building2,
        },
        {
            label: 'Ejecutado',
            value: formatMoney(stats.executed),
            icon: Building2,
        },
    ];

    return (
        <>
            <Head title="Obras y proyectos" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Obras y proyectos"
                        description="Registra, asigna responsables y da seguimiento a las obras de infraestructura del municipio."
                    />
                    <Button asChild>
                        <Link href="/admin/projects/create">
                            <HardHat />
                            Nueva obra
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {summaryCards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-normal">
                                    {card.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold">
                                    {card.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {stats.assigned > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-normal">
                                Ejecución presupuestaria global
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    {formatMoney(stats.assigned)} asignados
                                </span>
                                <span className="font-medium">
                                    {formatMoney(stats.executed)} ejecutados (
                                    {Math.round(
                                        (stats.executed / stats.assigned) * 100,
                                    )}
                                    %)
                                </span>
                            </div>
                            <div className="bg-muted h-2.5 w-full overflow-hidden rounded-full">
                                <div
                                    className="bg-primary h-full rounded-full"
                                    style={{
                                        width: `${Math.min(
                                            100,
                                            (stats.executed / stats.assigned) *
                                                100,
                                        )}%`,
                                    }}
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-wrap items-end gap-2">
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            className="w-64 pl-8"
                            placeholder="Buscar obra, contratista..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <div className="flex items-end gap-2">
                        <div className="space-y-1">
                            <Label className="text-xs">Inicia desde</Label>
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
                            <Label className="text-xs">Termina hasta</Label>
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
                        {hasActiveMunicipalityFilter && (
                            <div className="space-y-1">
                                <Label className="text-xs">Municipio</Label>
                                <Select
                                    value={filters.municipality_id ?? 'all'}
                                    onValueChange={(value) =>
                                        applyFilters({
                                            municipality_id:
                                                value === 'all'
                                                    ? undefined
                                                    : value,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-[210px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los municipios
                                        </SelectItem>
                                        {municipalities.map((item) => (
                                            <SelectItem
                                                key={item.id}
                                                value={String(item.id)}
                                            >
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-4 border-b pb-3 text-sm">
                    <ColumnMultiSelectFilter
                        label="Tipo"
                        options={types}
                        selected={filters.type ?? []}
                        onToggle={(value) => toggleFilter('type', value)}
                    />
                    <ColumnMultiSelectFilter
                        label="Estado"
                        options={statuses}
                        selected={filters.status ?? []}
                        onToggle={(value) => toggleFilter('status', value)}
                    />
                    <ColumnMultiSelectFilter
                        label="Sector"
                        options={sectors.map((sector) => ({
                            value: String(sector.id),
                            label: sector.name,
                        }))}
                        selected={filters.sector_id ?? []}
                        onToggle={(value) => toggleFilter('sector_id', value)}
                    />
                </div>

                {projects.data.length === 0 ? (
                    <div className="text-muted-foreground py-16 text-center">
                        No hay obras que coincidan con los filtros.
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {projects.data.map((project) => {
                            const assigned = Number(project.budget_assigned);
                            const executed = Number(project.budget_executed);
                            const budgetWidth =
                                assigned > 0
                                    ? Math.min(100, (executed / assigned) * 100)
                                    : 0;

                            return (
                                <Card
                                    key={project.id}
                                    className="flex flex-col"
                                >
                                    <CardHeader className="gap-3 pb-2">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="leading-snug font-semibold">
                                                    {project.name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {projectTypeLabel(
                                                        project.type,
                                                    )}
                                                    {project.sector
                                                        ? ` · ${project.sector.name}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <Badge
                                                className={statusBadgeClass(
                                                    project.status,
                                                )}
                                            >
                                                {projectStatusLabel(
                                                    project.status,
                                                )}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="flex flex-1 flex-col gap-3 text-sm">
                                        <div className="space-y-1">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-muted-foreground">
                                                    Progreso
                                                </span>
                                                <span className="font-medium">
                                                    {
                                                        project.progress_percentage
                                                    }
                                                    %
                                                </span>
                                            </div>
                                            <div className="bg-muted h-2 w-full overflow-hidden rounded-full">
                                                <div
                                                    className={`h-full rounded-full ${progressColorClass(project.progress_percentage)}`}
                                                    style={{
                                                        width: `${project.progress_percentage}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-1">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-muted-foreground">
                                                    Presupuesto
                                                </span>
                                                <span>
                                                    {formatMoney(executed)} /{' '}
                                                    {formatMoney(assigned)}
                                                </span>
                                            </div>
                                            <div className="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                                                <div
                                                    className="bg-primary h-full rounded-full"
                                                    style={{
                                                        width: `${budgetWidth}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>

                                        <dl className="text-muted-foreground grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                                            <div>
                                                <dt>Inicio plan.</dt>
                                                <dd className="text-foreground font-medium">
                                                    {formatDate(
                                                        project.start_date_planned,
                                                    )}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Fin plan.</dt>
                                                <dd className="text-foreground font-medium">
                                                    {formatDate(
                                                        project.end_date_planned,
                                                    )}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Municipio</dt>
                                                <dd className="text-foreground font-medium">
                                                    {project.municipality
                                                        ?.name ?? '—'}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Avances</dt>
                                                <dd className="text-foreground font-medium">
                                                    {project.updates_count}
                                                </dd>
                                            </div>
                                        </dl>

                                        <div className="mt-auto flex items-center justify-between pt-2">
                                            <span className="text-muted-foreground text-xs">
                                                {project.contractor_name ??
                                                    'Sin contratista'}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/admin/projects/${project.id}`}
                                                >
                                                    <Eye />
                                                    Ver detalle
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                <Pagination
                    links={projects.links}
                    from={projects.from ?? undefined}
                    to={projects.to ?? undefined}
                    total={projects.total}
                />
            </div>
        </>
    );
}
