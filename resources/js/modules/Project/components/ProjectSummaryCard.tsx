import { HardHat } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ProjectDetailRow } from '@/modules/Project/components/ProjectDetailRow';
import {
    formatDate,
    formatMoney,
    progressColorClass,
} from '@/modules/Project/helpers/projectFormatting';
import type { ProjectDetail } from '@/modules/Project/types/projectDetails';

export function ProjectSummaryCard({
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
                                {formatMoney(executed)} /{' '}
                                {formatMoney(assigned)}
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
                        <ProjectDetailRow
                            label="Presupuesto asignado"
                            value={formatMoney(project.budget_assigned)}
                        />
                        <ProjectDetailRow
                            label="Presupuesto ejecutado"
                            value={formatMoney(project.budget_executed)}
                        />
                        <ProjectDetailRow
                            label="Inicio planificado"
                            value={formatDate(project.start_date_planned)}
                        />
                        <ProjectDetailRow
                            label="Fin planificado"
                            value={formatDate(project.end_date_planned)}
                        />
                        <ProjectDetailRow
                            label="Inicio real"
                            value={formatDate(project.start_date_real)}
                        />
                        <ProjectDetailRow
                            label="Fin real"
                            value={formatDate(project.end_date_real)}
                        />
                        <ProjectDetailRow
                            label="Contratista"
                            value={project.contractor_name ?? '—'}
                        />
                        <ProjectDetailRow
                            label="Dirección"
                            value={project.address_text ?? '—'}
                        />
                        {project.latitude != null &&
                            project.longitude != null && (
                                <ProjectDetailRow
                                    label="Coordenadas"
                                    value={`${project.latitude.toFixed(5)}, ${project.longitude.toFixed(5)}`}
                                />
                            )}
                        <ProjectDetailRow
                            label="Registrada por"
                            value={project.creator?.name ?? '—'}
                        />
                    </div>
                </dl>
            </CardContent>
        </Card>
    );
}
