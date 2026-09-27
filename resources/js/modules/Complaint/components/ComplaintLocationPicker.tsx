import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPin } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    latitude: string;
    longitude: string;
    onChange: (latitude: string, longitude: string) => void;
};

export function ComplaintLocationPicker({
    latitude,
    longitude,
    onChange,
}: Props) {
    const containerRef = useRef<HTMLDivElement | null>(null);
    const mapRef = useRef<L.Map | null>(null);
    const markerRef = useRef<L.Marker | null>(null);

    useEffect(() => {
        if (!containerRef.current || mapRef.current) {
            return;
        }

        const lat = Number(latitude) || 18.4861;
        const lng = Number(longitude) || -69.9312;
        const map = L.map(containerRef.current, {
            center: [lat, lng],
            zoom: 12,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        map.on('click', (event: L.LeafletMouseEvent) => {
            onChange(event.latlng.lat.toFixed(6), event.latlng.lng.toFixed(6));
        });
        mapRef.current = map;

        return () => {
            map.remove();
            mapRef.current = null;
            markerRef.current = null;
        };
    }, []);

    useEffect(() => {
        const map = mapRef.current;
        if (!map) {
            return;
        }

        const lat = Number(latitude);
        const lng = Number(longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            markerRef.current?.remove();
            markerRef.current = null;
            return;
        }

        const markerIcon = L.divIcon({
            className: 'complaint-map-pin',
            html: '<span></span>',
            iconSize: [26, 26],
            iconAnchor: [13, 13],
        });
        const marker =
            markerRef.current ??
            L.marker([lat, lng], { icon: markerIcon }).addTo(map);
        marker.setLatLng([lat, lng]).setIcon(markerIcon);
        marker.on('dragend', () => {
            const position = marker.getLatLng();
            onChange(position.lat.toFixed(6), position.lng.toFixed(6));
        });
        markerRef.current = marker;
    }, [latitude, longitude, onChange]);

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm font-medium">
                    <MapPin className="size-4" />
                    Ubicación
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onChange('', '')}
                >
                    Limpiar
                </Button>
            </div>
            <div
                ref={containerRef}
                className="bg-muted h-64 w-full overflow-hidden rounded-lg border"
            />
            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                    <Label>Latitud</Label>
                    <Input
                        value={latitude}
                        onChange={(event) =>
                            onChange(event.target.value, longitude)
                        }
                        placeholder="18.4861"
                        inputMode="decimal"
                    />
                </div>
                <div className="space-y-1">
                    <Label>Longitud</Label>
                    <Input
                        value={longitude}
                        onChange={(event) =>
                            onChange(latitude, event.target.value)
                        }
                        placeholder="-69.9312"
                        inputMode="decimal"
                    />
                </div>
            </div>
            <p className="text-muted-foreground text-xs">
                Haz clic en el mapa para colocar la ubicación, o escribe las
                coordenadas. También puedes indicar la dirección de texto y
                omitir el mapa.
            </p>
            <style>{`
                .complaint-map-pin {
                    align-items: center;
                    display: flex;
                    justify-content: center;
                }
                .complaint-map-pin span {
                    background: #2563eb;
                    border: 2px solid #fff;
                    border-radius: 999px;
                    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25);
                    display: block;
                    height: 22px;
                    width: 22px;
                }
            `}</style>
        </div>
    );
}
