import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BarChart3,
    CalendarDays,
    Camera,
    Check,
    CheckCircle2,
    HardHat,
    MapPin,
    Pencil,
    Plus,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import {
    type MunicipalityMapRecord,
    type ProjectMapRecord,
    type SectorMapRecord,
    SectorBusinessMap,
} from '@/components/sector-business-map';
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
import { Textarea } from '@/components/ui/textarea';
import {
    formatDate,
    formatMoney,
    projectRoleLabel,
    projectStatusLabel,
    projectTypeLabel,
    progressColorClass,
    statusBadgeClass,
} from '@/lib/projects';

const NONE = '__none__';

type Option = { value: string; label: string };
type StatusOption = Option;

type SectorDetail = {
    id: number;
    name: string;
    geojson_polygon: GeoJsonPolygon | null;
};

type GeoJsonPolygon = {
    type: 'Polygon';
    coordinates: number[][][];
};

type PhotoRecord = {
    id: number;
    photo_url: string;
    caption: string | null;
    taken_at: string | null;
    uploader: { id: number; name: string } | null;
};

type UpdateRecord = {
    id: number;
    update_date: string;
    progress_percentage_at_update: number;
    description: string | null;
    status_at_update: string | null;
    budget_spent_at_update: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
    photos: PhotoRecord[];
};

type ProjectUserRecord = {
    id: number;
    role_in_project: string;
    assigned_at: string | null;
    user: { id: number; name: string } | null;
};

type MilestoneRecord = {
    id: number;
    name: string;
    order: number;
    planned_date: string | null;
    completed_date: string | null;
    status: string;
};

type ProjectDetail = {
    id: number;
    name: string;
    type: string;
    status: string;
    description: string | null;
    latitude: number | null;
    longitude: number | null;
    address_text: string | null;
    budget_assigned: string | null;
    budget_executed: string | null;
    progress_percentage: number;
    contractor_name: string | null;
    start_date_planned: string | null;
    end_date_planned: string | null;
    start_date_real: string | null;
    end_date_real: string | null;
    created_at: string;
    municipality: { id: number; name: string };
    sector: SectorDetail | null;
    creator: { id: number; name: string } | null;
    project_users: ProjectUserRecord[];
    updates: UpdateRecord[];
    photos: PhotoRecord[];
    milestones: MilestoneRecord[];
};

type Props = {
    project: ProjectDetail;
    users: { id: number; name: string }[];
    roles: Option[];
    statuses: StatusOption[];
    map: {
        municipality: MunicipalityMapRecord | null;
        municipalities: MunicipalityMapRecord[];
        sectors: SectorMapRecord[];
    };
};

const TRANSITIONS: Record<string, string[]> = {
    planned: ['in_progress', 'cancelled'],
    in_progress: ['paused', 'completed', 'cancelled'],
    paused: ['in_progress', 'cancelled'],
    completed: ['in_progress'],
    cancelled: [],
};

const MILESTONE_LABELS: Record<string, string> = {
    pending: 'Pendiente',
    completed: 'Completado',
    delayed: 'Atrasado',
};

function milestoneStyle(status: string) {
    switch (status) {
        case 'completed':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'delayed':
            return 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400';
        default:
            return 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-400';
    }
}

export default function AdminProjectShow({ project, users, roles, statuses, map }: Props) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [milestoneOpen, setMilestoneOpen] = useState(false);

    const assignment = useForm({ user_id: NONE, role_in_project: 'collaborator' });
    const statusForm = useForm({ status: '', note: '' });
    const milestoneForm = useForm({ name: '', planned_date: '' });

    const nextStatuses = TRANSITIONS[project.status] ?? [];
    const assigned = Number(project.budget_assigned);
    const executed = Number(project.budget_executed);
    const budgetWidth = assigned > 0 ? Math.min(100, (executed / assigned) * 100) : 0;

    const projectMarker: ProjectMapRecord = {
        id: project.id,
        name: project.name,
        type: project.type,
        status: project.status,
        progress_percentage: project.progress_percentage,
        latitude: project.latitude,
        longitude: project.longitude,
        sector_id: project.sector?.id ?? null,
        municipality_id: project.municipality?.id ?? null,
        sector: project.sector ? { id: project.sector.id, name: project.sector.name } : null,
        municipality: project.municipality
            ? { id: project.municipality.id, name: project.municipality.name }
            : null,
    };

    const submitAssignment = (e: React.FormEvent) => {
        e.preventDefault();
        assignment.transform((values) => ({
            user_id: values.user_id === NONE ? undefined : Number(values.user_id),
            role_in_project: values.role_in_project,
        }));
        assignment.post(`/admin/projects/${project.id}/users`, {
            preserveScroll: true,
            onSuccess: () => {
                assignment.reset();
                setAssignOpen(false);
            },
        });
    };

    const changeRole = (member: ProjectUserRecord, role: string) => {
        router.post(
            `/admin/projects/${project.id}/users`,
            { user_id: member.user?.id, role_in_project: role },
            { preserveScroll: true },
        );
    };

    const removeMember = (member: ProjectUserRecord) => {
        if (confirm(`¿Retirar a ${member.user?.name ?? 'este integrante'} del equipo?`)) {
            router.delete(`/admin/projects/${project.id}/users/${member.id}`, {
                preserveScroll: true,
            });
        }
    };

    const submitStatus = (e: React.FormEvent) => {
        e.preventDefault();
        statusForm.patch(`/admin/projects/${project.id}/status`, {
            preserveScroll: true,
            onSuccess: () => statusForm.reset(),
        });
    };

    const submitMilestone = (e: React.FormEvent) => {
        e.preventDefault();
        milestoneForm.post(`/admin/projects/${project.id}/milestones`, {
            preserveScroll: true,
            onSuccess: () => {
                milestoneForm.reset();
                setMilestoneOpen(false);
            },
        });
    };

    const setMilestoneStatus = (milestone: MilestoneRecord, value: string) => {
        router.patch(
            `/admin/projects/${project.id}/milestones/${milestone.id}/status`,
            { status: value },
            { preserveScroll: true },
        );
    };

    const removeMilestone = (milestone: MilestoneRecord) => {
        if (confirm(`¿Eliminar el hito "${milestone.name}"?`)) {
            router.delete(`/admin/projects/${project.id}/milestones/${milestone.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title={project.name} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/projects')}
                            className="mb-2"
                        >
                            <ArrowLeft />
                            Volver
                        </Button>
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-xl font-semibold tracking-tight">
                                {project.name}
                            </h2>
                            <Badge className={statusBadgeClass(project.status)}>
                                {projectStatusLabel(project.status)}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {projectTypeLabel(project.type)}
                            {project.sector ? ` · ${project.sector.name}` : ''}
                            {' · '}
                            {project.municipality.name}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/admin/projects/${project.id}/edit`}>
                                <Pencil />
                                Editar
                            </Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link
                                href={`/admin/projects/${project.id}/updates/create`}
                            >
                                <Plus />
                                Registrar avance
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <SummaryCard
                            project={project}
                            budgetWidth={budgetWidth}
                        />

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MapPin className="size-4" />
                                    Ubicación de la obra
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {project.latitude != null && project.longitude != null ? (
                                    <SectorBusinessMap
                                        sectors={map.sectors}
                                        municipalities={map.municipalities}
                                        businesses={[]}
                                        discoveredPlaces={[]}
                                        projects={[projectMarker]}
                                        selectedProjectId={project.id}
                                        readOnly
                                        onBusinessSelect={() => {}}
                                        onPlaceSelect={() => {}}
                                        onDiscoverPlaces={() => {}}
                                        onMapClick={() => {}}
                                        onCustomPolygonChange={() => {}}
                                    />
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        La obra no tiene coordenadas registradas.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <TimelineCard project={project} />

                        <MilestonesCard
                            project={project}
                            open={milestoneOpen}
                            setOpen={setMilestoneOpen}
                            form={milestoneForm}
                            onSubmit={submitMilestone}
                            onStatusChange={setMilestoneStatus}
                            onRemove={removeMilestone}
                        />

                        <GalleryCard photos={project.photos} />
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Users className="size-4" />
                                    Equipo de la obra
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {project.project_users.map((member) => (
                                    <div
                                        key={member.id}
                                        className="border-sidebar-border/60 flex items-center justify-between gap-3 rounded-lg border p-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {member.user?.name ?? '—'}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {projectRoleLabel(
                                                    member.role_in_project,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-1">
                                            <Select
                                                value={member.role_in_project}
                                                onValueChange={(value) =>
                                                    changeRole(member, value)
                                                }
                                            >
                                                <SelectTrigger className="h-8 w-[140px]">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles.map((role) => (
                                                        <SelectItem
                                                            key={role.value}
                                                            value={role.value}
                                                        >
                                                            {role.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:text-destructive size-8"
                                                onClick={() => removeMember(member)}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                                {project.project_users.length === 0 && (
                                    <p className="text-muted-foreground text-sm">
                                        Sin integrantes asignados.
                                    </p>
                                )}

                                {assignOpen ? (
                                    <form
                                        onSubmit={submitAssignment}
                                        className="space-y-2 border-t pt-3"
                                    >
                                        <div className="grid grid-cols-2 gap-2">
                                            <Select
                                                value={assignment.data.user_id}
                                                onValueChange={(value) =>
                                                    assignment.setData(
                                                        'user_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Usuario" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {users.map((user) => (
                                                        <SelectItem
                                                            key={user.id}
                                                            value={String(
                                                                user.id,
                                                            )}
                                                        >
                                                            {user.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Select
                                                value={
                                                    assignment.data
                                                        .role_in_project
                                                }
                                                onValueChange={(value) =>
                                                    assignment.setData(
                                                        'role_in_project',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles.map((role) => (
                                                        <SelectItem
                                                            key={role.value}
                                                            value={role.value}
                                                        >
                                                            {role.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setAssignOpen(false)
                                                }
                                            >
                                                Cancelar
                                            </Button>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                disabled={
                                                    assignment.processing ||
                                                    assignment.data.user_id ===
                                                        NONE
                                                }
                                            >
                                                Asignar
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => setAssignOpen(true)}
                                    >
                                        <UserPlus />
                                        Agregar integrante
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <BarChart3 className="size-4" />
                                    Estado de la obra
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {nextStatuses.length > 0 ? (
                                    <form
                                        onSubmit={submitStatus}
                                        className="space-y-3"
                                    >
                                        <div className="space-y-1">
                                            <Label>Nuevo estado</Label>
                                            <Select
                                                value={statusForm.data.status}
                                                onValueChange={(value) =>
                                                    statusForm.setData(
                                                        'status',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Selecciona" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {nextStatuses
                                                        .map((value) =>
                                                            statuses.find(
                                                                (item) =>
                                                                    item.value ===
                                                                    value,
                                                            ),
                                                        )
                                                        .filter(Boolean)
                                                        .map((status) => (
                                                            <SelectItem
                                                                key={status!.value}
                                                                value={
                                                                    status!.value
                                                                }
                                                            >
                                                                {status!.label}
                                                            </SelectItem>
                                                        ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-1">
                                            <Label>Nota (opcional)</Label>
                                            <Textarea
                                                rows={3}
                                                value={statusForm.data.note}
                                                onChange={(e) =>
                                                    statusForm.setData(
                                                        'note',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Comenta el motivo del cambio..."
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={
                                                statusForm.processing ||
                                                !statusForm.data.status
                                            }
                                        >
                                            Actualizar estado
                                        </Button>
                                    </form>
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        La obra se encuentra en un estado final.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

function SummaryCard({
    project,
    budgetWidth,
}: {
    project: ProjectDetail;
    budgetWidth: number;
}) {
    const executed = Number(project.budget_executed);
    const assigned = Number(project.budget_assigned);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HardHat className="size-4" />
                    Resumen general
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-5">
                {project.description && (
                    <p className="text-sm leading-relaxed">
                        {project.description}
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <div className="flex items-center justify-between text-xs">
                            <span className="text-muted-foreground">
                                Progreso de ejecución
                            </span>
                            <span className="font-medium">
                                {project.progress_percentage}%
                            </span>
                        </div>
                        <div className="bg-muted h-2.5 w-full overflow-hidden rounded-full">
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
                                {formatMoney(executed)} / {formatMoney(assigned)}
                            </span>
                        </div>
                        <div className="bg-muted h-2.5 w-full overflow-hidden rounded-full">
                            <div
                                className="bg-primary h-full rounded-full"
                                style={{ width: `${budgetWidth}%` }}
                            />
                        </div>
                    </div>
                </div>

                <dl className="text-sm">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Detail
                            label="Presupuesto asignado"
                            value={formatMoney(project.budget_assigned)}
                        />
                        <Detail
                            label="Presupuesto ejecutado"
                            value={formatMoney(project.budget_executed)}
                        />
                        <Detail
                            label="Inicio planificado"
                            value={formatDate(project.start_date_planned)}
                        />
                        <Detail
                            label="Fin planificado"
                            value={formatDate(project.end_date_planned)}
                        />
                        <Detail
                            label="Inicio real"
                            value={formatDate(project.start_date_real)}
                        />
                        <Detail
                            label="Fin real"
                            value={formatDate(project.end_date_real)}
                        />
                        <Detail
                            label="Contratista"
                            value={project.contractor_name ?? '—'}
                        />
                        <Detail
                            label="Dirección"
                            value={project.address_text ?? '—'}
                        />
                        {project.latitude != null && project.longitude != null && (
                            <Detail
                                label="Coordenadas"
                                value={`${project.latitude.toFixed(5)}, ${project.longitude.toFixed(5)}`}
                            />
                        )}
                        <Detail
                            label="Registrada por"
                            value={project.creator?.name ?? '—'}
                        />
                    </div>
                </dl>
            </CardContent>
        </Card>
    );
}

function TimelineCard({ project }: { project: ProjectDetail }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CalendarDays className="size-4" />
                    Línea de tiempo de avances
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {project.updates.map((update) => (
                        <div
                            key={update.id}
                            className="border-sidebar-border/50 rounded-lg border p-4"
                        >
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Badge
                                    className={statusBadgeClass(
                                        update.status_at_update ??
                                            project.status,
                                    )}
                                >
                                    {projectStatusLabel(
                                        update.status_at_update ??
                                            project.status,
                                    )}
                                </Badge>
                                <span className="text-sm font-semibold">
                                    {update.progress_percentage_at_update}%
                                </span>
                                {update.budget_spent_at_update && (
                                    <span className="text-muted-foreground text-xs">
                                        Gasto: {formatMoney(update.budget_spent_at_update)}
                                    </span>
                                )}
                            </div>
                            {update.description && (
                                <p className="text-sm">{update.description}</p>
                            )}
                            <p className="text-muted-foreground mt-2 text-xs">
                                {formatDate(update.update_date)}
                                {update.user?.name
                                    ? ` · ${update.user.name}`
                                    : ''}
                            </p>
                            {update.photos.length > 0 && (
                                <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    {update.photos.map((photo) => (
                                        <a
                                            key={photo.id}
                                            href={photo.photo_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            title={photo.caption ?? undefined}
                                        >
                                            <img
                                                src={photo.photo_url}
                                                alt={photo.caption ?? 'Foto del avance'}
                                                className="bg-muted aspect-video w-full rounded-md object-cover"
                                            />
                                        </a>
                                    ))}
                                </div>
                            )}
                        </div>
                    ))}
                    {project.updates.length === 0 && (
                        <p className="text-muted-foreground text-sm">
                            Todavía no hay avances registrados. Registra el
                            primero para comenzar la bitácora.
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function MilestonesCard({
    project,
    open,
    setOpen,
    form,
    onSubmit,
    onStatusChange,
    onRemove,
}: {
    project: ProjectDetail;
    open: boolean;
    setOpen: (open: boolean) => void;
    form: ReturnType<typeof useForm<{ name: string; planned_date: string }>>;
    onSubmit: (e: React.FormEvent) => void;
    onStatusChange: (milestone: MilestoneRecord, value: string) => void;
    onRemove: (milestone: MilestoneRecord) => void;
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between gap-2">
                    <CardTitle className="flex items-center gap-2">
                        <CheckCircle2 className="size-4" />
                        Hitos
                    </CardTitle>
                    {!open && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setOpen(true)}
                        >
                            <Plus />
                            Agregar hito
                        </Button>
                    )}
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                {project.milestones.map((milestone, index) => (
                    <div
                        key={milestone.id}
                        className="border-sidebar-border/60 flex items-center justify-between gap-3 rounded-lg border p-3"
                    >
                        <div className="flex min-w-0 items-start gap-3">
                            <span className="text-muted-foreground mt-0.5 text-xs font-semibold">
                                {index + 1}.
                            </span>
                            <div className="min-w-0">
                                <p className="text-sm font-medium">
                                    {milestone.name}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    Planeado:{' '}
                                    {formatDate(milestone.planned_date)}
                                    {milestone.completed_date
                                        ? ` · Completado: ${formatDate(milestone.completed_date)}`
                                        : ''}
                                </p>
                            </div>
                        </div>
                        <div className="flex shrink-0 items-center gap-1">
                            <Badge className={milestoneStyle(milestone.status)}>
                                {MILESTONE_LABELS[milestone.status] ??
                                    milestone.status}
                            </Badge>
                            <Select
                                value={milestone.status}
                                onValueChange={(value) =>
                                    onStatusChange(milestone, value)
                                }
                            >
                                <SelectTrigger className="h-8 w-[130px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">
                                        Pendiente
                                    </SelectItem>
                                    <SelectItem value="completed">
                                        Completado
                                    </SelectItem>
                                    <SelectItem value="delayed">
                                        Atrasado
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="text-destructive hover:text-destructive size-8"
                                onClick={() => onRemove(milestone)}
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </div>
                ))}
                {project.milestones.length === 0 && !open && (
                    <p className="text-muted-foreground text-sm">
                        La obra no tiene hitos definidos.
                    </p>
                )}

                {open && (
                    <form onSubmit={onSubmit} className="space-y-2 border-t pt-3">
                        <div className="space-y-1">
                            <Label>Nombre del hito</Label>
                            <Input
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="Ej. Cimentación"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label>Fecha planeada</Label>
                            <Input
                                type="date"
                                value={form.data.planned_date}
                                onChange={(e) =>
                                    form.setData(
                                        'planned_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing || !form.data.name}
                            >
                                <Check />
                                Guardar hito
                            </Button>
                        </div>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function GalleryCard({ photos }: { photos: PhotoRecord[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Camera className="size-4" />
                    Galería de la obra ({photos.length})
                </CardTitle>
            </CardHeader>
            <CardContent>
                {photos.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Sin fotos registradas.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                        {photos.map((photo) => (
                            <a
                                key={photo.id}
                                href={photo.photo_url}
                                target="_blank"
                                rel="noreferrer"
                                title={photo.caption ?? undefined}
                                className="group relative"
                            >
                                <img
                                    src={photo.photo_url}
                                    alt={photo.caption ?? 'Foto de la obra'}
                                    className="bg-muted aspect-square w-full rounded-md object-cover"
                                />
                            </a>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function Detail({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="grid grid-cols-[130px_1fr] items-baseline gap-2 border-b border-dashed last:border-0">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="pb-2 font-medium break-words text-sm">{value}</dd>
        </div>
    );
}
