import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { MapDetail } from '@/modules/Business/components/MapDetail';
import type {
    BusinessMapRecord,
    RegistrationStatus,
} from '@/modules/Business/components/SectorBusinessMap';

const labels: Record<RegistrationStatus, string> = {
    registered: 'Registrado',
    unregistered: 'No registrado',
    pending_verification: 'Pendiente',
};
const classes: Record<RegistrationStatus, string> = {
    registered: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    unregistered: 'bg-red-100 text-red-800 border-red-200',
    pending_verification: 'bg-amber-100 text-amber-800 border-amber-200',
};

type Props = {
    business: BusinessMapRecord;
    onStatusChange: (status: RegistrationStatus) => void;
};

export function BusinessDetails({ business, onStatusChange }: Props) {
    return (
        <div className="space-y-4 text-sm">
            <div>
                <h3 className="font-semibold">{business.name}</h3>
                <p className="text-muted-foreground">
                    {business.category || 'Sin categoria'}
                </p>
            </div>
            <span
                className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-medium ${classes[business.registration_status]}`}
            >
                {labels[business.registration_status]}
            </span>
            <dl className="space-y-2">
                <MapDetail
                    label="Sector"
                    value={business.sector?.name ?? '—'}
                />
                <MapDetail label="RNC" value={business.rnc ?? '—'} />
                <MapDetail
                    label="Direccion"
                    value={business.address_text ?? '—'}
                />
                <MapDetail
                    label="Coordenadas"
                    value={`${business.latitude}, ${business.longitude}`}
                />
                <MapDetail
                    label="Ultima verificacion"
                    value={business.last_verified_at ?? '—'}
                />
            </dl>
            <Select
                value={business.registration_status}
                onValueChange={(value) =>
                    onStatusChange(value as RegistrationStatus)
                }
            >
                <SelectTrigger className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {Object.entries(labels).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                            {label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
