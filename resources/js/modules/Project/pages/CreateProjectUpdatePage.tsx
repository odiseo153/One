import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Camera,
    Check,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { projectService } from '@/modules/Project/services/projectService';
import type { ProjectUpdateFormData } from '@/modules/Project/types/projectForms';
import {
    formatMoney,
    projectStatusLabel,
    projectTypeLabel,
    statusBadgeClass,
} from '@/modules/Project/helpers/projectFormatting';

type Option = { value: string; label: string };

const KEEP = '__keep__';

type ProjectBrief = {
    id: number;
    name: string;
    type: string;
    status: string;
    progress_percentage: number;
    budget_assigned: string | null;
    budget_executed: string | null;
    sector: { id: number; name: string } | null;
    municipality: { id: number; name: string };
};

type PhotoRow = { file: File | null; caption: string };

type Props = {
    project: ProjectBrief;
    statuses: Option[];
};

function todayInput() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 10);
}

export default function CreateProjectUpdate({ project, statuses }: Props) {
    const [photoRows, setPhotoRows] = useState<PhotoRow[]>([
        { file: null, caption: '' },
    ]);

    const form = useForm<ProjectUpdateFormData>({
        update_date: todayInput(),
        progress_percentage: String(project.progress_percentage),
        description: '',
        status: KEEP,
        budget_spent: '',
        taken_at: todayInput(),
        photos: [] as File[],
        captions: [] as string[],
    });

    const updateRow = (index: number, patch: Partial<PhotoRow>) => {
        setPhotoRows((current) =>
            current.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );
    };

    const removeRow = (index: number) => {
        setPhotoRows((current) => current.filter((_, i) => i !== index));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const files: File[] = [];
        const captions: string[] = [];

        photoRows.forEach((row) => {
            if (!row.file) {
                return;
            }

            files.push(row.file);
            captions.push(row.caption);
        });

        form.setData('photos', files);
        form.setData('captions', captions);

        projectService.saveUpdate(form, project.id);
    };

    return (
        <>
            <Head title="Registrar avance" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <Link
                        href={`/admin/projects/${project.id}`}
                        className="text-muted-foreground hover:text-foreground mb-2 inline-flex items-center gap-1 text-sm"
                    >
                        <ArrowLeft className="size-4" />
                        Volver a la obra
                    </Link>
                    <Heading
                        title="Registrar avance"
                        description="Registra el avance de la obra, actualiza su progreso y adjunta fotos del trabajo realizado."
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex flex-wrap items-center gap-2 text-base">
                            {project.name}
                            <Badge className={statusBadgeClass(project.status)}>
                                {projectStatusLabel(project.status)}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <p className="text-muted-foreground text-xs">
                                Tipo
                            </p>
                            <p className="font-medium">
                                {projectTypeLabel(project.type)}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground text-xs">
                                Progreso actual
                            </p>
                            <p className="font-medium">
                                {project.progress_percentage}%
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground text-xs">
                                Presupuesto
                            </p>
                            <p className="font-medium">
                                {formatMoney(project.budget_executed)} /{' '}
                                {formatMoney(project.budget_assigned)}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <CalendarDays className="size-4" />
                                Datos del avance
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Fecha del avance</Label>
                                <Input
                                    type="date"
                                    value={form.data.update_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'update_date',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.update_date} />
                            </div>
                            <div className="space-y-2">
                                <Label>Progreso de la obra (%)</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    max={100}
                                    value={form.data.progress_percentage}
                                    onChange={(e) =>
                                        form.setData(
                                            'progress_percentage',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.progress_percentage}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Estado de la obra (opcional)</Label>
                                <Select
                                    value={form.data.status}
                                    onValueChange={(value) =>
                                        form.setData('status', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Mantener el actual" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={KEEP}>
                                            Mantener el actual
                                        </SelectItem>
                                        {statuses.map((status) => (
                                            <SelectItem
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.status} />
                            </div>
                            <div className="space-y-2">
                                <Label>Gasto en este avance (RD$)</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={form.data.budget_spent}
                                    onChange={(e) =>
                                        form.setData(
                                            'budget_spent',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.budget_spent}
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Descripción del avance</Label>
                                <Textarea
                                    rows={4}
                                    value={form.data.description}
                                    onChange={(e) =>
                                        form.setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Describe qué se ejecutó en esta etapa..."
                                />
                                <InputError message={form.errors.description} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between gap-2">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Camera className="size-4" />
                                    Fotos del avance
                                </CardTitle>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setPhotoRows((current) => [
                                            ...current,
                                            { file: null, caption: '' },
                                        ])
                                    }
                                >
                                    <Plus />
                                    Agregar foto
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {photoRows.map((row, index) => (
                                <div
                                    key={index}
                                    className="border-sidebar-border/60 grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_1.6fr_auto]"
                                >
                                    <div className="space-y-1">
                                        <Label>Imagen</Label>
                                        <Input
                                            type="file"
                                            accept="image/*"
                                            onChange={(e) =>
                                                updateRow(index, {
                                                    file:
                                                        e.target.files?.[0] ??
                                                        null,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label>Leyenda (opcional)</Label>
                                        <Input
                                            value={row.caption}
                                            onChange={(e) =>
                                                updateRow(index, {
                                                    caption: e.target.value,
                                                })
                                            }
                                            placeholder="Describe la foto..."
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="text-destructive hover:text-destructive"
                                            disabled={photoRows.length === 1}
                                            onClick={() => removeRow(index)}
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                            {form.errors.photos && (
                                <p className="text-destructive text-sm">
                                    {form.errors.photos}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            <Check />
                            Guardar avance
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
