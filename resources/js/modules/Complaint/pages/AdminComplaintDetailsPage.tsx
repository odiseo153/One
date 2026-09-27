import { Head, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    MapPin,
    MessageSquareText,
    Phone,
    User,
    UserPlus,
} from 'lucide-react';
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
    COMPLAINT_TRANSITIONS,
    complaintStatusClass,
} from '@/modules/Complaint/helpers/complaintStatus';
import { complaintService } from '@/modules/Complaint/services/complaintService';
import type {
    ComplaintAssignmentData,
    ComplaintStatusData,
} from '@/modules/Complaint/types/complaint';
import { formatDateTime } from '@/shared/helpers/date';

const EMPTY = '__empty__';

const CATEGORY_LABELS: Record<string, string> = {
    bache: 'Bache',
    alumbrado: 'Alumbrado',
    basura: 'Basura',
    agua: 'Agua',
    otro: 'Otro',
};

type StatusOption = { value: string; label: string };

type Complaint = {
    id: number;
    tracking_code: string;
    category: string;
    description: string;
    latitude: number | null;
    longitude: number | null;
    address_text: string | null;
    photo_url: string | null;
    citizen_name: string;
    citizen_phone: string;
    status: string;
    resolved_at: string | null;
    created_at: string;
    municipality: { id: number; name: string };
    sector: { name: string } | null;
    assigned_user: { id: number; name: string } | null;
    updates: {
        id: number;
        note: string | null;
        previous_status: string | null;
        new_status: string;
        created_at: string;
        user: { name: string } | null;
    }[];
};

type Props = {
    complaint: Complaint;
    users: { id: number; name: string }[];
    statuses: StatusOption[];
};

function statusLabel(status: string | null, statuses: StatusOption[]) {
    return (
        statuses.find((item) => item.value === status)?.label ?? status ?? '—'
    );
}

export default function AdminComplaintShow({
    complaint,
    users,
    statuses,
}: Props) {
    const assignment = useForm<ComplaintAssignmentData>({
        assigned_user_id: EMPTY,
    });
    const statusForm = useForm<ComplaintStatusData>({ status: '', note: '' });

    const nextStatuses = COMPLAINT_TRANSITIONS[complaint.status] ?? [];

    const assign = (e: React.FormEvent) => {
        e.preventDefault();
        complaintService.assign(assignment, complaint.id);
    };

    const submitStatus = (e: React.FormEvent) => {
        e.preventDefault();
        complaintService.updateStatus(statusForm, complaint.id);
    };

    return (
        <>
            <Head title={`Queja ${complaint.tracking_code}`} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => complaintService.openAdminList()}
                            className="mb-2"
                        >
                            <ArrowLeft />
                            Volver
                        </Button>
                        <h2 className="text-xl font-semibold tracking-tight">
                            Queja {complaint.tracking_code}
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Reportada el {formatDateTime(complaint.created_at)}{' '}
                            · {complaint.municipality.name}
                            {complaint.sector
                                ? ` · ${complaint.sector.name}`
                                : ''}
                        </p>
                    </div>
                    <Badge className={complaintStatusClass(complaint.status)}>
                        {statusLabel(complaint.status, statuses)}
                    </Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MessageSquareText className="size-4" />
                                    Detalle de la queja
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        Categoría
                                    </p>
                                    <p className="text-sm font-medium">
                                        {CATEGORY_LABELS[complaint.category] ??
                                            complaint.category}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        Descripción
                                    </p>
                                    <p className="text-sm leading-relaxed">
                                        {complaint.description}
                                    </p>
                                </div>
                                {complaint.photo_url && (
                                    <img
                                        src={complaint.photo_url}
                                        alt="Foto de la queja"
                                        className="bg-muted h-48 w-full rounded-md object-cover"
                                    />
                                )}
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        Resuelta
                                    </p>
                                    <p className="text-sm">
                                        {complaint.resolved_at
                                            ? formatDateTime(
                                                  complaint.resolved_at,
                                              )
                                            : '—'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MapPin className="size-4" />
                                    Ubicación y contacto
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        Dirección
                                    </p>
                                    <p>
                                        {complaint.address_text ??
                                            'No indicada'}
                                    </p>
                                </div>
                                {complaint.latitude !== null &&
                                    complaint.longitude !== null && (
                                        <div className="flex items-center gap-2">
                                            <MapPin className="text-muted-foreground size-4" />
                                            {complaint.latitude.toFixed(5)},{' '}
                                            {complaint.longitude.toFixed(5)}
                                        </div>
                                    )}
                                <div className="flex items-center gap-2">
                                    <User className="text-muted-foreground size-4" />
                                    {complaint.citizen_name}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Phone className="text-muted-foreground size-4" />
                                    {complaint.citizen_phone}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CheckCircle2 className="size-4" />
                                    Historial de seguimiento
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {complaint.updates.map((update) => (
                                        <div
                                            key={update.id}
                                            className="border-sidebar-border/50 rounded-lg border p-4"
                                        >
                                            <div className="mb-2 flex items-center gap-2">
                                                <Badge
                                                    className={complaintStatusClass(
                                                        update.new_status,
                                                    )}
                                                >
                                                    {statusLabel(
                                                        update.new_status,
                                                        statuses,
                                                    )}
                                                </Badge>
                                            </div>
                                            {update.note && (
                                                <p className="text-sm">
                                                    {update.note}
                                                </p>
                                            )}
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {formatDateTime(
                                                    update.created_at,
                                                )}
                                                {update.user?.name
                                                    ? ` · ${update.user.name}`
                                                    : ''}
                                            </p>
                                        </div>
                                    ))}
                                    {complaint.updates.length === 0 && (
                                        <p className="text-muted-foreground text-sm">
                                            Sin registros.
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <UserPlus className="size-4" />
                                    Asignación
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={assign} className="space-y-3">
                                    <div className="space-y-1">
                                        <Label>Responsable</Label>
                                        <Select
                                            value={
                                                complaint.assigned_user
                                                    ? String(
                                                          complaint
                                                              .assigned_user.id,
                                                      )
                                                    : EMPTY
                                            }
                                            onValueChange={(value) =>
                                                assignment.setData(
                                                    'assigned_user_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={EMPTY}>
                                                    Sin asignar
                                                </SelectItem>
                                                {users.map((user) => (
                                                    <SelectItem
                                                        key={user.id}
                                                        value={String(user.id)}
                                                    >
                                                        {user.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {complaint.assigned_user && (
                                        <p className="text-muted-foreground text-xs">
                                            Actualmente asignada a{' '}
                                            {complaint.assigned_user.name}.
                                        </p>
                                    )}
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={assignment.processing}
                                    >
                                        Guardar asignación
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Cambiar estado</CardTitle>
                            </CardHeader>
                            <CardContent>
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
                                            placeholder="Comenta el avance..."
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={statusForm.processing}
                                    >
                                        Actualizar estado
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
