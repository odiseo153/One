import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BarChart3,
    MapPin,
    Pencil,
    Plus,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import {
    type MunicipalityMapRecord,
    type ProjectMapRecord,
    type SectorMapRecord,
    SectorBusinessMap,
} from '@/modules/Business/components/SectorBusinessMap';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    projectRoleLabel,
    projectStatusLabel,
    projectTypeLabel,
    statusBadgeClass,
} from '@/modules/Project/helpers/projectFormatting';
import { ProjectGalleryCard } from '@/modules/Project/components/ProjectGalleryCard';
import { ProjectMilestonesCard } from '@/modules/Project/components/ProjectMilestonesCard';
import { ProjectSummaryCard } from '@/modules/Project/components/ProjectSummaryCard';
import { ProjectTimelineCard } from '@/modules/Project/components/ProjectTimelineCard';
import { projectService } from '@/modules/Project/services/projectService';
import type {
    ProjectAssignmentFormData,
    ProjectMilestoneFormData,
    ProjectStatusFormData,
} from '@/modules/Project/types/projectForms';
import type {
    MilestoneRecord,
    ProjectDetail,
    ProjectUserRecord,
} from '@/modules/Project/types/projectDetails';

const NONE = '__none__';

type Option = { value: string; label: string };
type StatusOption = Option;

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

export default function AdminProjectShow({
    project,
    users,
    roles,
    statuses,
    map,
}: Props) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [milestoneOpen, setMilestoneOpen] = useState(false);

    const assignment = useForm<ProjectAssignmentFormData>({
        user_id: NONE,
        role_in_project: 'collaborator',
    });
    const statusForm = useForm<ProjectStatusFormData>({ status: '', note: '' });
    const milestoneForm = useForm<ProjectMilestoneFormData>({
        name: '',
        planned_date: '',
    });

    const nextStatuses = TRANSITIONS[project.status] ?? [];
    const assigned = Number(project.budget_assigned);
    const executed = Number(project.budget_executed);
    const budgetWidth =
        assigned > 0 ? Math.min(100, (executed / assigned) * 100) : 0;

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
        sector: project.sector
            ? { id: project.sector.id, name: project.sector.name }
            : null,
        municipality: project.municipality
            ? { id: project.municipality.id, name: project.municipality.name }
            : null,
    };

    const submitAssignment = (e: React.FormEvent) => {
        e.preventDefault();
        projectService.assign(assignment, project.id, () => {
            assignment.reset();
            setAssignOpen(false);
        });
    };

    const changeRole = (member: ProjectUserRecord, role: string) => {
        projectService.changeRole(project.id, member.user?.id, role);
    };

    const removeMember = (member: ProjectUserRecord) => {
        if (
            confirm(
                `¿Retirar a ${member.user?.name ?? 'este integrante'} del equipo?`,
            )
        ) {
            projectService.removeMember(project.id, member.id);
        }
    };

    const submitStatus = (e: React.FormEvent) => {
        e.preventDefault();
        projectService.updateStatus(statusForm, project.id);
    };

    const submitMilestone = (e: React.FormEvent) => {
        e.preventDefault();
        projectService.createMilestone(milestoneForm, project.id, () => {
            milestoneForm.reset();
            setMilestoneOpen(false);
        });
    };

    const setMilestoneStatus = (milestone: MilestoneRecord, value: string) => {
        projectService.updateMilestone(project.id, milestone.id, value);
    };

    const removeMilestone = (milestone: MilestoneRecord) => {
        if (confirm(`¿Eliminar el hito "${milestone.name}"?`)) {
            projectService.removeMilestone(project.id, milestone.id);
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
                            onClick={() => projectService.openList()}
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
                        <ProjectSummaryCard
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
                                {project.latitude != null &&
                                project.longitude != null ? (
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
                                        La obra no tiene coordenadas
                                        registradas.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <ProjectTimelineCard project={project} />

                        <ProjectMilestonesCard
                            project={project}
                            open={milestoneOpen}
                            setOpen={setMilestoneOpen}
                            form={milestoneForm}
                            onSubmit={submitMilestone}
                            onStatusChange={setMilestoneStatus}
                            onRemove={removeMilestone}
                        />

                        <ProjectGalleryCard photos={project.photos} />
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
                                                onClick={() =>
                                                    removeMember(member)
                                                }
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
                                                                key={
                                                                    status!
                                                                        .value
                                                                }
                                                                value={
                                                                    status!
                                                                        .value
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
