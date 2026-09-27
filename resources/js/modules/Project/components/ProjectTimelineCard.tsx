import { CalendarDays } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatDate,
    formatMoney,
    projectStatusLabel,
    statusBadgeClass,
} from '@/modules/Project/helpers/projectFormatting';
import type { ProjectDetail } from '@/modules/Project/types/projectDetails';

export function ProjectTimelineCard({ project }: { project: ProjectDetail }) {
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
                                        Gasto:{' '}
                                        {formatMoney(
                                            update.budget_spent_at_update,
                                        )}
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
                                                alt={
                                                    photo.caption ??
                                                    'Foto del avance'
                                                }
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
