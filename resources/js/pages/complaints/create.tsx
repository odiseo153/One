import { Head, Link, useForm } from '@inertiajs/react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    LocateFixed,
    MapPin,
    MessageSquareText,
    RefreshCcw,
    Send,
    Upload,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import {
    SearchableSelect,
    type SearchableSelectOption,
} from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const NONE = '__none__';

type MunicipalityOption = { id: number; name: string };
type SectorOption = { id: number; municipality_id: number; name: string };
type CategoryOption = { value: string; label: string };

type Props = {
    municipalities: MunicipalityOption[];
    sectors: SectorOption[];
    categories: CategoryOption[];
    municipality_id: number | null;
};

function LocationPicker({
    latitude,
    longitude,
    onChange,
}: {
    latitude: string;
    longitude: string;
    onChange: (latitude: string, longitude: string) => void;
}) {
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
            if (markerRef.current) {
                markerRef.current.remove();
                markerRef.current = null;
            }
            return;
        }

        const markerIcon = L.divIcon({
            className: 'complaint-map-pin',
            html: `<span></span>`,
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
    }, [latitude, longitude]);

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
                        onChange={(e) => onChange(e.target.value, longitude)}
                        placeholder="18.4861"
                        inputMode="decimal"
                    />
                </div>
                <div className="space-y-1">
                    <Label>Longitud</Label>
                    <Input
                        value={longitude}
                        onChange={(e) => onChange(latitude, e.target.value)}
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

export default function CreateComplaint({
    municipalities,
    sectors,
    categories,
    municipality_id,
}: Props) {
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);

    const form = useForm({
        municipality_id: municipality_id ? String(municipality_id) : NONE,
        sector_id: NONE,
        category: '',
        description: '',
        latitude: '',
        longitude: '',
        address_text: '',
        photo: null as File | null,
        citizen_name: '',
        citizen_phone: '',
    });

    const municipalityOptions: SearchableSelectOption[] = useMemo(
        () =>
            municipalities.map((municipality) => ({
                value: String(municipality.id),
                label: municipality.name,
            })),
        [municipalities],
    );

    const sectorOptions: SearchableSelectOption[] = useMemo(
        () => [
            { value: NONE, label: 'Sin sector' },
            ...sectors
                .filter(
                    (sector) =>
                        Number(form.data.municipality_id) ===
                        sector.municipality_id,
                )
                .map((sector) => ({
                    value: String(sector.id),
                    label: sector.name,
                })),
        ],
        [sectors, form.data.municipality_id],
    );

    const categoryOptions: SearchableSelectOption[] = useMemo(
        () =>
            categories.map((category) => ({
                value: category.value,
                label: category.label,
            })),
        [categories],
    );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((values) => ({
            ...values,
            municipality_id:
                values.municipality_id === NONE
                    ? null
                    : Number(values.municipality_id),
            sector_id:
                values.sector_id === NONE ? null : Number(values.sector_id),
            latitude: values.latitude === '' ? null : Number(values.latitude),
            longitude:
                values.longitude === '' ? null : Number(values.longitude),
            photo: values.photo ?? undefined,
        }));
        form.post('/quejas', {
            forceFormData: true,
            onSuccess: () => {
                form.reset();
                setPhotoPreview(null);
            },
        });
    };

    const setPhoto = (file: File | null) => {
        form.setData('photo', file);
        if (file) {
            setPhotoPreview(URL.createObjectURL(file));
        } else {
            setPhotoPreview(null);
        }
    };

    return (
        <>
            <Head title="Reportar queja" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Reportar un problema
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Cuéntanos qué está sucediendo en tu comunidad y lo
                        reportaremos al municipio correspondiente.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <LocateFixed className="size-4" />
                                Información de la queja
                            </CardTitle>
                            <CardDescription>
                                Selecciona el municipio, sector y categoría del
                                problema.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Municipio</Label>
                                <SearchableSelect
                                    value={form.data.municipality_id}
                                    onValueChange={(value) => {
                                        form.setData('municipality_id', value);
                                        form.setData('sector_id', NONE);
                                    }}
                                    options={municipalityOptions}
                                    placeholder="Selecciona un municipio"
                                    emptyText="No se encontró ningún municipio."
                                />
                                <InputError
                                    message={form.errors.municipality_id}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Sector</Label>
                                <SearchableSelect
                                    value={form.data.sector_id}
                                    onValueChange={(value) =>
                                        form.setData('sector_id', value)
                                    }
                                    options={sectorOptions}
                                    placeholder="Selecciona un sector"
                                    emptyText="No se encontró ningún sector."
                                />
                                <InputError message={form.errors.sector_id} />
                            </div>
                            <div className="space-y-2">
                                <Label>Categoría</Label>
                                <SearchableSelect
                                    value={form.data.category}
                                    onValueChange={(value) =>
                                        form.setData('category', value)
                                    }
                                    options={categoryOptions}
                                    placeholder="Selecciona"
                                    emptyText="No se encontró ninguna categoría."
                                />
                                <InputError message={form.errors.category} />
                            </div>
                            <div className="space-y-2">
                                <Label>Foto (opcional)</Label>
                                <label className="border-input placeholder:text-muted-foreground hover:bg-accent/40 flex h-9 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm">
                                    <Upload className="size-4" />
                                    <span className="truncate">
                                        {form.data.photo
                                            ? form.data.photo.name
                                            : 'Subir imagen'}
                                    </span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        className="hidden"
                                        onChange={(e) =>
                                            setPhoto(
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                </label>
                                {photoPreview && (
                                    <img
                                        src={photoPreview}
                                        alt="Vista previa"
                                        className="bg-muted h-28 w-full rounded-md object-cover"
                                    />
                                )}
                                <InputError message={form.errors.photo} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Descripción</Label>
                                <Textarea
                                    rows={4}
                                    value={form.data.description}
                                    onChange={(e) =>
                                        form.setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Describe el problema de forma clara (mínimo 10 caracteres)..."
                                />
                                <InputError message={form.errors.description} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="size-4" />
                                Ubicación del problema
                            </CardTitle>
                            <CardDescription>
                                Indica la dirección o colócala en el mapa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Dirección (opcional)</Label>
                                <Input
                                    value={form.data.address_text}
                                    onChange={(e) =>
                                        form.setData(
                                            'address_text',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Ej. Av. Independencia, esquina Calle Duarte"
                                />
                                <InputError
                                    message={form.errors.address_text}
                                />
                            </div>
                            <LocationPicker
                                latitude={form.data.latitude}
                                longitude={form.data.longitude}
                                onChange={(latitude, longitude) => {
                                    form.setData('latitude', latitude);
                                    form.setData('longitude', longitude);
                                }}
                            />
                            <InputError message={form.errors.latitude} />
                            <InputError message={form.errors.longitude} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MessageSquareText className="size-4" />
                                Datos de contacto
                            </CardTitle>
                            <CardDescription>
                                Te contactaremos para darte seguimiento. No
                                necesitas tener una cuenta.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Nombre</Label>
                                <Input
                                    value={form.data.citizen_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'citizen_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Nombre de la persona."
                                />
                                <InputError
                                    message={form.errors.citizen_name}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Teléfono</Label>
                                <Input
                                    value={form.data.citizen_phone}
                                    onChange={(e) =>
                                        form.setData(
                                            'citizen_phone',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="+1 809 000 0000"
                                />
                                <InputError
                                    message={form.errors.citizen_phone}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => form.reset()}
                        >
                            <RefreshCcw />
                            Limpiar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            <Send />
                            {form.processing ? 'Enviando...' : 'Enviar queja'}
                        </Button>
                    </div>
                </form>

                <p className="text-muted-foreground text-center text-xs">
                    Al enviar recibirás un código de seguimiento. También puedes
                    consultar el estado en la página{' '}
                    <Link
                        href="/quejas/track"
                        className="text-primary underline underline-offset-4"
                    >
                        Consultar estado
                    </Link>
                    .
                </p>
            </div>
        </>
    );
}
