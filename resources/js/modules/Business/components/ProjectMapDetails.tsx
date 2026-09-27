import { HardHat } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { MapDetail } from '@/modules/Business/components/MapDetail';
import type { ProjectMapRecord } from '@/modules/Business/components/SectorBusinessMap';
import {
    PROJECT_STATUS_COLORS,
    projectStatusLabel,
    projectTypeLabel,
} from '@/modules/Project/helpers/projectFormatting';

type Props = { project: ProjectMapRecord; onOpen: () => void };

export function ProjectMapDetails({ project, onOpen }: Props) {
    const color =
        PROJECT_STATUS_COLORS[
            project.status as keyof typeof PROJECT_STATUS_COLORS
        ] ?? '#9ca3af';

    return (
        <div className="mt-3 space-y-3 rounded-lg border p-3 text-sm">
            <div>
                <h3 className="font-semibold break-words">{project.name}</h3>
                <p className="text-muted-foreground mt-0.5">
                    {projectTypeLabel(project.type)}
                </p>
                <span
                    className="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    style={{ background: `${color}1a`, color }}
                >
                    <span
                        className="inline-block size-2 rounded-full"
                        style={{ background: color }}
                    />
                    {projectStatusLabel(project.status)}
                </span>
            </div>
            <dl className="space-y-2">
                <MapDetail label="Sector" value={project.sector?.name ?? '—'} />
                <MapDetail
                    label="Municipio"
                    value={project.municipality?.name ?? '—'}
                />
            </dl>
            <div className="space-y-1">
                <div className="flex items-center justify-between text-xs">
                    <span className="text-muted-foreground">Progreso</span>
                    <span>{project.progress_percentage}%</span>
                </div>
                <div className="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                    <div
                        className="h-full rounded-full"
                        style={{
                            width: `${project.progress_percentage}%`,
                            background: color,
                        }}
                    />
                </div>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="w-full"
                onClick={onOpen}
            >
                <HardHat /> Ver detalle de la obra
            </Button>
        </div>
    );
}
