import { Button } from '@/components/ui/button';
import { MapDetail } from '@/modules/Business/components/MapDetail';
import type { DiscoveredPlaceRecord } from '@/modules/Business/components/SectorBusinessMap';

type Props = { place: DiscoveredPlaceRecord; onCreate: () => void };

export function PlaceDetails({ place, onCreate }: Props) {
    return (
        <div className="mt-4 space-y-3 text-sm">
            <div>
                <h3 className="font-semibold">{place.title}</h3>
                <p className="text-muted-foreground">
                    {place.category || 'Sin categoria'}
                </p>
            </div>
            <span
                className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-medium ${place.registered ? 'border-emerald-200 bg-emerald-100 text-emerald-800' : 'border-red-200 bg-red-100 text-red-800'}`}
            >
                {place.registered ? 'Registrado' : 'No registrado'}
            </span>
            <dl className="space-y-2">
                <MapDetail label="Direccion" value={place.address ?? '—'} />
                <MapDetail label="Telefono" value={place.phone ?? '—'} />
                <MapDetail
                    label="Rating"
                    value={
                        place.rating
                            ? `${place.rating}${place.reviews ? ` (${place.reviews})` : ''}`
                            : '—'
                    }
                />
                <MapDetail
                    label="Coordenadas"
                    value={`${place.latitude}, ${place.longitude}`}
                />
            </dl>
            {!place.business_id ? (
                <Button type="button" className="w-full" onClick={onCreate}>
                    Registrar local
                </Button>
            ) : null}
        </div>
    );
}
