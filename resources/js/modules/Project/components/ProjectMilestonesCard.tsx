import { Check, CheckCircle2, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
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
import { formatDate } from '@/modules/Project/helpers/projectFormatting';
import type {
    MilestoneRecord,
    ProjectDetail,
} from '@/modules/Project/types/projectDetails';

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

type MilestoneForm = {
    data: { name: string; planned_date: string };
    setData: (key: 'name' | 'planned_date', value: string) => void;
    processing: boolean;
};

export function ProjectMilestonesCard({
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
    form: MilestoneForm;
    onSubmit: (event: FormEvent) => void;
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
                    <form
                        onSubmit={onSubmit}
                        className="space-y-2 border-t pt-3"
                    >
                        <div className="space-y-1">
                            <Label>Nombre del hito</Label>
                            <Input
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder="Ej. Cimentación"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label>Fecha planeada</Label>
                            <Input
                                type="date"
                                value={form.data.planned_date}
                                onChange={(event) =>
                                    form.setData(
                                        'planned_date',
                                        event.target.value,
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
