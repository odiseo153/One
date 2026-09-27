import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    Copy,
    MapPin,
    MessageSquareText,
    Phone,
    User,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { complaintStatusClass } from '@/modules/Complaint/helpers/complaintStatus';
import { formatDateTime } from '@/shared/helpers/date';

type StatusLabel = 'Recibida' | 'En proceso' | 'Resuelta';

type Complaint = {
    tracking_code: string;
    category: string;
    category_label: string;
    description: string;
    latitude: number | null;
    longitude: number | null;
    address_text: string | null;
    photo_url: string | null;
    citizen_name: string;
    citizen_phone: string;
    status: string;
    status_label: StatusLabel;
    created_at: string;
    municipality: { name: string };
    sector: { name: string } | null;
    updates: {
        status_label: StatusLabel;
        note: string | null;
        created_at: string;
        by_name: string | null;
    }[];
};

export default function ShowComplaint({ complaint }: { complaint: Complaint }) {
    const [copied, setCopied] = useState(false);

    const copyCode = async () => {
        try {
            await navigator.clipboard.writeText(complaint.tracking_code);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <>
            <Head title={`Queja ${complaint.tracking_code}`} />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Estado de tu queja
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Verificada en {complaint.municipality.name}
                            {complaint.sector
                                ? ` · Sector ${complaint.sector.name}`
                                : ''}
                            .
                        </p>
                    </div>
                    <Badge className={complaintStatusClass(complaint.status)}>
                        {complaint.status_label}
                    </Badge>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Tu código de seguimiento</CardTitle>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between gap-4">
                        <span className="font-mono text-xl font-semibold tracking-widest">
                            {complaint.tracking_code}
                        </span>
                        <Button variant="outline" size="sm" onClick={copyCode}>
                            <Copy />
                            {copied ? 'Copiado' : 'Copiar'}
                        </Button>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MessageSquareText className="size-4" />
                                Detalle
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-muted-foreground text-xs">
                                    Categoría
                                </p>
                                <p className="font-medium">
                                    {complaint.category_label}
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
                            <div>
                                <p className="text-muted-foreground text-xs">
                                    Fecha de reporte
                                </p>
                                <p className="text-sm">
                                    {formatDateTime(complaint.created_at)}
                                </p>
                            </div>
                            {complaint.photo_url && (
                                <img
                                    src={complaint.photo_url}
                                    alt="Foto de la queja"
                                    className="bg-muted h-40 w-full rounded-md object-cover"
                                />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="size-4" />
                                Ubicación y contacto
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="text-sm">
                                <p className="text-muted-foreground text-xs">
                                    Dirección
                                </p>
                                <p className="font-medium">
                                    {complaint.address_text ?? 'No indicada'}
                                </p>
                            </div>
                            {complaint.latitude !== null &&
                                complaint.longitude !== null && (
                                    <div className="flex items-center gap-2 text-sm">
                                        <MapPin className="text-muted-foreground size-4" />
                                        <span>
                                            {complaint.latitude.toFixed(5)},{' '}
                                            {complaint.longitude.toFixed(5)}
                                        </span>
                                    </div>
                                )}
                            <div className="flex items-center gap-2 text-sm">
                                <User className="text-muted-foreground size-4" />
                                {complaint.citizen_name}
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Phone className="text-muted-foreground size-4" />
                                {complaint.citizen_phone}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CheckCircle2 className="size-4" />
                            Historial de seguimiento
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-0">
                            <div className="flex gap-4">
                                <div className="flex flex-col items-center">
                                    <span className="bg-primary size-2.5 rounded-full" />
                                    <span className="bg-border w-px flex-1" />
                                </div>
                                <div className="flex-1 pb-6">
                                    <p className="text-sm font-medium">
                                        Recepción
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {formatDateTime(complaint.created_at)}
                                    </p>
                                </div>
                            </div>
                            {complaint.updates.map((update, index) => (
                                <div
                                    key={`${update.created_at}-${index}`}
                                    className="flex gap-4"
                                >
                                    <div className="flex flex-col items-center">
                                        <span className="bg-primary size-2.5 rounded-full" />
                                        <span className="bg-border w-px flex-1" />
                                    </div>
                                    <div className="flex-1 pb-6">
                                        <p className="text-sm font-medium">
                                            {update.status_label}
                                        </p>
                                        {update.note && (
                                            <p className="text-muted-foreground text-xs">
                                                {update.note}
                                            </p>
                                        )}
                                        <p className="text-muted-foreground text-xs">
                                            {formatDateTime(update.created_at)}
                                            {update.by_name
                                                ? ` · ${update.by_name}`
                                                : ''}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <p className="text-muted-foreground text-center text-xs">
                    ¿Necesitas reportar otro problema?{' '}
                    <Link
                        href="/quejas"
                        className="text-primary underline underline-offset-4"
                    >
                        Reporta una nueva queja
                    </Link>
                    .
                </p>
            </div>
        </>
    );
}
