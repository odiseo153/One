import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { BusinessEmployeesFields } from '@/modules/Business/components/BusinessEmployeesFields';
import type { BusinessMapRecord } from '@/modules/Business/components/SectorBusinessMap';
import {
    DOCUMENT_TYPES,
    PROPERTY_STATUSES,
    type DocumentType,
    type PropertyStatus,
} from '@/modules/Business/constants/businessOptions';
import {
    documentNumberMaxLength,
    formatDocumentNumber,
} from '@/modules/Business/helpers/documentNumber';
import { useBusinessCategories } from '@/modules/Business/hooks/useBusinessCategories';
import type { BusinessFormData } from '@/modules/Business/types/businessForm';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    editing: BusinessMapRecord | null;
    form: InertiaFormProps<BusinessFormData>;
    sectors: { id: number; name: string }[];
    inspectors: { id: number; name: string }[];
    onSubmit: (event: FormEvent) => void;
};

export function BusinessFormDialog({
    open,
    onOpenChange,
    editing,
    form,
    sectors,
    inspectors,
    onSubmit,
}: Props) {
    const errors = form.errors as Record<string, string>;
    const { categories, loading: categoriesLoading } =
        useBusinessCategories(open);
    const hasCurrentCategory = categories.some(
        (category) => category.name === form.data.category,
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {editing ? 'Editar local' : 'Crear local'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={onSubmit} className="grid gap-4 md:grid-cols-2">
                    <Field label="Nombre" error={form.errors.name}>
                        <Input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Categoría" error={form.errors.category}>
                        <Select
                            value={form.data.category || 'none'}
                            onValueChange={(value) =>
                                form.setData('category', value)
                            }
                            disabled={categoriesLoading}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue
                                    placeholder={
                                        categoriesLoading
                                            ? 'Cargando categorías...'
                                            : 'Seleccionar categoría'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    Sin categoría
                                </SelectItem>
                                {form.data.category &&
                                form.data.category !== 'none' &&
                                !hasCurrentCategory ? (
                                    <SelectItem value={form.data.category}>
                                        {form.data.category}
                                    </SelectItem>
                                ) : null}
                                {categories.map((category) => (
                                    <SelectItem
                                        key={category.id}
                                        value={category.name}
                                    >
                                        {category.code} — {category.name}
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
                                <SelectItem value="none">Sin sector</SelectItem>
                                {sectors.map((sector) => (
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
                    <Field
                        label="Estado de la propiedad"
                        error={form.errors.property_status}
                    >
                        <Select
                            value={String(form.data.property_status)}
                            onValueChange={(value) =>
                                form.setData(
                                    'property_status',
                                    Number(value) as PropertyStatus,
                                )
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {PROPERTY_STATUSES.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={String(option.value)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field label="Latitud" error={form.errors.latitude}>
                        <Input
                            type="number"
                            step="0.0000001"
                            value={form.data.latitude}
                            onChange={(event) =>
                                form.setData('latitude', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Longitud" error={form.errors.longitude}>
                        <Input
                            type="number"
                            step="0.0000001"
                            value={form.data.longitude}
                            onChange={(event) =>
                                form.setData('longitude', event.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Tipo de documento"
                        error={form.errors.document_type}
                    >
                        <Select
                            value={String(form.data.document_type)}
                            onValueChange={(value) => {
                                const type = Number(value) as DocumentType;
                                form.setData({
                                    ...form.data,
                                    document_type: type,
                                    document_number: formatDocumentNumber(
                                        type,
                                        form.data.document_number,
                                    ),
                                });
                            }}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Seleccionar" />
                            </SelectTrigger>
                            <SelectContent>
                                {DOCUMENT_TYPES.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={String(option.value)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field
                        label="Número de documento"
                        error={form.errors.document_number}
                    >
                        <Input
                            inputMode={
                                form.data.document_type === 2
                                    ? 'text'
                                    : 'numeric'
                            }
                            maxLength={documentNumberMaxLength(
                                form.data.document_type,
                            )}
                            value={form.data.document_number}
                            onChange={(event) =>
                                form.setData(
                                    'document_number',
                                    formatDocumentNumber(
                                        form.data.document_type,
                                        event.target.value,
                                    ),
                                )
                            }
                        />
                    </Field>
                    <div className="flex items-center gap-3 rounded-lg border p-3 md:col-span-2">
                        <Checkbox
                            id="is_registered"
                            checked={form.data.is_registered}
                            onCheckedChange={(checked) =>
                                form.setData('is_registered', checked === true)
                            }
                        />
                        <Label htmlFor="is_registered">
                            El negocio está registrado
                        </Label>
                    </div>
                    <Field label="RNC" error={form.errors.rnc}>
                        <Input
                            inputMode="numeric"
                            maxLength={9}
                            value={form.data.rnc}
                            onChange={(event) =>
                                form.setData('rnc', event.target.value)
                            }
                            required={form.data.is_registered}
                        />
                    </Field>
                    <Field
                        label="Actividad principal"
                        error={form.errors.primary_activity}
                    >
                        <Input
                            value={form.data.primary_activity}
                            onChange={(event) =>
                                form.setData(
                                    'primary_activity',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                    <Field
                        label="Actividad secundaria"
                        error={form.errors.secondary_activity}
                    >
                        <Input
                            value={form.data.secondary_activity}
                            onChange={(event) =>
                                form.setData(
                                    'secondary_activity',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                    <Field label="Foto del lugar" error={form.errors.photo}>
                        <Input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) =>
                                form.setData(
                                    'photo',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                        />
                        {editing?.photo_url ? (
                            <a
                                className="text-muted-foreground text-xs underline"
                                href={editing.photo_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Ver foto actual
                            </a>
                        ) : null}
                    </Field>
                    <Field label="Inspector" error={form.errors.inspector_id}>
                        <Select
                            value={form.data.inspector_id}
                            onValueChange={(value) =>
                                form.setData('inspector_id', value)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    Sin inspector
                                </SelectItem>
                                {inspectors.map((inspector) => (
                                    <SelectItem
                                        key={inspector.id}
                                        value={String(inspector.id)}
                                    >
                                        {inspector.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field label="Detectado" error={form.errors.detected_at}>
                        <Input
                            type="date"
                            value={form.data.detected_at}
                            onChange={(event) =>
                                form.setData('detected_at', event.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Verificado"
                        error={form.errors.last_verified_at}
                    >
                        <Input
                            type="date"
                            value={form.data.last_verified_at}
                            onChange={(event) =>
                                form.setData(
                                    'last_verified_at',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                    <div className="space-y-2 md:col-span-2">
                        <Label>Dirección</Label>
                        <Textarea
                            value={form.data.address_text}
                            onChange={(event) =>
                                form.setData('address_text', event.target.value)
                            }
                        />
                        <InputError message={form.errors.address_text} />
                    </div>
                    <BusinessEmployeesFields
                        employees={form.data.employees}
                        errors={errors}
                        onChange={(employees) =>
                            form.setData('employees', employees)
                        }
                    />
                    <DialogFooter className="md:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Guardar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
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
