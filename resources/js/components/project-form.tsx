import { router, useForm } from '@inertiajs/react';
import { HardHat } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import type { ProjectType } from '@/lib/projects';
import { PROJECT_TYPES, projectTypeLabel } from '@/lib/projects';

const NONE = '__none__';

export type ProjectFormProject = {
    id?: number;
    name?: string;
    type?: string;
    description?: string | null;
    sector_id?: number | null;
    latitude?: number | null;
    longitude?: number | null;
    address_text?: string | null;
    budget_assigned?: string | number | null;
    start_date_planned?: string | null;
    end_date_planned?: string | null;
    contractor_name?: string | null;
};

type MunicipalityOption = { id: number; name: string };
type SectorOption = { id: number; municipality_id: number; name: string };

type Props = {
    project?: ProjectFormProject;
    editing?: boolean;
    municipalities: MunicipalityOption[];
    fixedMunicipality: MunicipalityOption | null;
    sectors: SectorOption[];
};

export function ProjectForm({
    project,
    editing = false,
    municipalities,
    fixedMunicipality,
    sectors,
}: Props) {
    const [municipalityError, setMunicipalityError] = useState<string | null>(
        null,
    );

    const form = useForm({
        municipality_id: fixedMunicipality
            ? String(fixedMunicipality.id)
            : '',
        sector_id: project?.sector_id ? String(project.sector_id) : NONE,
        name: project?.name ?? '',
        type: (project?.type ?? 'street') as ProjectType,
        description: project?.description ?? '',
        address_text: project?.address_text ?? '',
        latitude: project?.latitude != null ? String(project.latitude) : '',
        longitude: project?.longitude != null ? String(project.longitude) : '',
        budget_assigned:
            project?.budget_assigned != null
                ? String(project.budget_assigned)
                : '',
        start_date_planned: project?.start_date_planned ?? '',
        end_date_planned: project?.end_date_planned ?? '',
        contractor_name: project?.contractor_name ?? '',
    });

    const selectedMunicipalityId = form.data.municipality_id
        ? Number(form.data.municipality_id)
        : null;

    const availableSectors = sectors.filter(
        (sector) =>
            !selectedMunicipalityId ||
            sector.municipality_id === selectedMunicipalityId,
    );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!fixedMunicipality && !form.data.municipality_id) {
            setMunicipalityError('Selecciona el municipio de la obra.');
            return;
        }

        setMunicipalityError(null);

        form.transform((values) => ({
            ...values,
            municipality_id: Number(values.municipality_id),
            sector_id:
                values.sector_id === NONE ? null : Number(values.sector_id),
            latitude: values.latitude === '' ? null : Number(values.latitude),
            longitude:
                values.longitude === '' ? null : Number(values.longitude),
            budget_assigned:
                values.budget_assigned === ''
                    ? null
                    : Number(values.budget_assigned),
            description: values.description || null,
            address_text: values.address_text || null,
            contractor_name: values.contractor_name || null,
            start_date_planned: values.start_date_planned || null,
            end_date_planned: values.end_date_planned || null,
        }));

        if (editing) {
            form.patch(`/admin/projects/${project?.id}`, {
                preserveScroll: true,
            });
            return;
        }

        form.post('/admin/projects', { preserveScroll: true });
    };

    const pickMunicipality = (value: string) => {
        form.setData('municipality_id', value);
        form.setData('sector_id', NONE);
        setMunicipalityError(null);
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
                {!fixedMunicipality && (
                    <Field
                        label="Municipio *"
                        error={municipalityError ?? form.errors.municipality_id}
                    >
                        <Select
                            value={form.data.municipality_id}
                            onValueChange={pickMunicipality}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Selecciona el municipio" />
                            </SelectTrigger>
                            <SelectContent>
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
                    </Field>
                )}

                <Field label="Nombre *" error={form.errors.name}>
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ej. Repavimentación de la Av. Principal"
                    />
                </Field>

                <Field label="Tipo de obra *" error={form.errors.type}>
                    <Select
                        value={form.data.type}
                        onValueChange={(value) =>
                            form.setData('type', value as ProjectType)
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PROJECT_TYPES.map((type) => (
                                <SelectItem key={type} value={type}>
                                    {projectTypeLabel(type)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <Field label="Sector" error={form.errors.sector_id}>
                    <Select
                        value={form.data.sector_id}
                        onValueChange={(value) =>
                            form.setData('sector_id', value)
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>Sin sector</SelectItem>
                            {availableSectors.map((sector) => (
                                <SelectItem
                                    key={sector.id}
                                    value={String(sector.id)}
                                >
                                    {sector.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <Field label="Dirección" error={form.errors.address_text}>
                    <Input
                        value={form.data.address_text}
                        onChange={(e) =>
                            form.setData('address_text', e.target.value)
                        }
                        placeholder="Dirección o referencia de la obra"
                    />
                </Field>

                <Field
                    label="Presupuesto asignado (RD$)"
                    error={form.errors.budget_assigned}
                >
                    <Input
                        type="number"
                        min="0"
                        step="0.01"
                        value={form.data.budget_assigned}
                        onChange={(e) =>
                            form.setData('budget_assigned', e.target.value)
                        }
                        placeholder="0.00"
                    />
                </Field>

                <Field label="Contratista" error={form.errors.contractor_name}>
                    <Input
                        value={form.data.contractor_name}
                        onChange={(e) =>
                            form.setData('contractor_name', e.target.value)
                        }
                        placeholder="Empresa o contratista a cargo"
                    />
                </Field>

                <Field label="Latitud" error={form.errors.latitude}>
                    <Input
                        type="number"
                        step="0.0000001"
                        value={form.data.latitude}
                        onChange={(e) => form.setData('latitude', e.target.value)}
                        placeholder="-90 a 90"
                    />
                </Field>

                <Field label="Longitud" error={form.errors.longitude}>
                    <Input
                        type="number"
                        step="0.0000001"
                        value={form.data.longitude}
                        onChange={(e) =>
                            form.setData('longitude', e.target.value)
                        }
                        placeholder="-180 a 180"
                    />
                </Field>

                <Field
                    label="Inicio planificado"
                    error={form.errors.start_date_planned}
                >
                    <Input
                        type="date"
                        value={form.data.start_date_planned}
                        onChange={(e) =>
                            form.setData(
                                'start_date_planned',
                                e.target.value,
                            )
                        }
                    />
                </Field>

                <Field
                    label="Fin planificado"
                    error={form.errors.end_date_planned}
                >
                    <Input
                        type="date"
                        value={form.data.end_date_planned}
                        onChange={(e) =>
                            form.setData('end_date_planned', e.target.value)
                        }
                    />
                </Field>

                <div className="space-y-2 md:col-span-2">
                    <Label>Descripción</Label>
                    <Textarea
                        rows={4}
                        value={form.data.description}
                        onChange={(e) =>
                            form.setData('description', e.target.value)
                        }
                        placeholder="Alcance general de la obra..."
                    />
                    <InputError message={form.errors.description} />
                </div>
            </div>

            <div className="flex justify-end gap-2 border-t pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.visit('/admin/projects')}
                >
                    Cancelar
                </Button>
                <Button type="submit" disabled={form.processing}>
                    <HardHat />
                    {editing ? 'Guardar cambios' : 'Crear obra'}
                </Button>
            </div>
        </form>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
