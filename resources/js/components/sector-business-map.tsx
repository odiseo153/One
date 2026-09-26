import {
    booleanPointInPolygon,
    point,
    polygon as turfPolygon,
} from '@turf/turf';
import L, {
    type LatLngBoundsExpression,
    type LeafletMouseEvent,
    type Map as LeafletMap,
} from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { RotateCcw, Trash2, Undo2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { PROJECT_STATUS_COLORS, projectStatusLabel } from '@/lib/projects';

export type RegistrationStatus =
    | 'registered'
    | 'unregistered'
    | 'pending_verification';

export type BusinessMapRecord = {
    id: number;
    municipality_id: number;
    sector_id: number | null;
    sector: { id: number; name: string } | null;
    name: string;
    category: string | null;
    latitude: number;
    longitude: number;
    address_text: string | null;
    registration_status: RegistrationStatus;
    rnc: string | null;
    detected_at: string | null;
    last_verified_at: string | null;
    inspector_id: number | null;
};

export type DiscoveredPlaceRecord = {
    id: string;
    title: string;
    category: string | null;
    address: string | null;
    phone: string | null;
    rating: number | string | null;
    reviews: number | string | null;
    latitude: number;
    longitude: number;
    municipality_id: number | null;
    sector_id: number | null;
    registered: boolean;
    business_id: number | null;
    registration_status: RegistrationStatus | null;
};

export type SectorMapRecord = {
    id: number;
    municipality_id: number;
    name: string;
    geojson_polygon: GeoJsonPolygon | null;
};

export type MunicipalityMapRecord = {
    id: number;
    province_id: number | null;
    name: string;
    geojson_polygon: GeoJsonPolygon | null;
};

export type ProjectMapRecord = {
    id: number;
    name: string;
    type: string;
    status: string;
    progress_percentage: number;
    latitude: number | null;
    longitude: number | null;
    sector_id: number | null;
    municipality_id: number | null;
    sector: { id: number; name: string } | null;
    municipality: { id: number; name: string } | null;
};

export type GeoJsonPolygon = {
    type: 'Polygon';
    coordinates: number[][][];
};

type PolygonPoint = {
    lat: number;
    lng: number;
};

type Summary = Record<RegistrationStatus | 'total', number>;

type Props = {
    sectors: SectorMapRecord[];
    municipalities: MunicipalityMapRecord[];
    businesses: BusinessMapRecord[];
    discoveredPlaces: DiscoveredPlaceRecord[];
    projects?: ProjectMapRecord[];
    selectedBusinessId?: number | null;
    selectedPlaceId?: string | null;
    selectedProjectId?: number | null;
    readOnly?: boolean;
    onBusinessSelect: (business: BusinessMapRecord) => void;
    onPlaceSelect: (place: DiscoveredPlaceRecord) => void;
    onProjectSelect?: (project: ProjectMapRecord) => void;
    onDiscoverPlaces: (point: PolygonPoint) => void;
    onMapClick: (
        point: PolygonPoint,
        sectorId: number | null,
        municipalityId: number | null,
    ) => void;
    onCustomPolygonChange: (
        polygon: GeoJsonPolygon | null,
        summary: Summary,
    ) => void;
};

const statusColors: Record<RegistrationStatus, string> = {
    registered: '#16a34a',
    unregistered: '#dc2626',
    pending_verification: '#d97706',
};

const emptySummary: Summary = {
    total: 0,
    registered: 0,
    unregistered: 0,
    pending_verification: 0,
};

function getPolygonPoints(value?: GeoJsonPolygon | null): PolygonPoint[] {
    const ring = Array.isArray(value?.coordinates?.[0])
        ? value.coordinates[0]
        : [];
    const normalizedRing =
        ring.length > 1 &&
        ring[0]?.[0] === ring[ring.length - 1]?.[0] &&
        ring[0]?.[1] === ring[ring.length - 1]?.[1]
            ? ring.slice(0, -1)
            : ring;

    return normalizedRing
        .filter(
            (coordinate) =>
                Array.isArray(coordinate) &&
                Number.isFinite(coordinate[0]) &&
                Number.isFinite(coordinate[1]),
        )
        .map((coordinate) => ({
            lng: Number(coordinate[0]),
            lat: Number(coordinate[1]),
        }));
}

function buildPolygon(points: PolygonPoint[]): GeoJsonPolygon | null {
    const validPoints = points.filter(
        (item) => Number.isFinite(item.lat) && Number.isFinite(item.lng),
    );

    if (validPoints.length < 3) {
        return null;
    }

    const coordinates = validPoints.map((item) => [item.lng, item.lat]);
    coordinates.push([validPoints[0].lng, validPoints[0].lat]);

    return {
        type: 'Polygon',
        coordinates: [coordinates],
    };
}

function pointInGeoJson(pointValue: PolygonPoint, value: GeoJsonPolygon) {
    try {
        return booleanPointInPolygon(
            point([pointValue.lng, pointValue.lat]),
            turfPolygon(value.coordinates),
        );
    } catch {
        return false;
    }
}

function resolveSectorId(pointValue: PolygonPoint, sectors: SectorMapRecord[]) {
    return (
        sectors.find(
            (sector) =>
                sector.geojson_polygon &&
                pointInGeoJson(pointValue, sector.geojson_polygon),
        )?.id ?? null
    );
}

function resolveMunicipalityId(
    pointValue: PolygonPoint,
    municipalities: MunicipalityMapRecord[],
) {
    return (
        municipalities.find(
            (municipality) =>
                municipality.geojson_polygon &&
                pointInGeoJson(pointValue, municipality.geojson_polygon),
        )?.id ?? null
    );
}

function summarizeBusinesses(
    businesses: BusinessMapRecord[],
    polygon: GeoJsonPolygon | null,
): Summary {
    if (!polygon) {
        return emptySummary;
    }

    return businesses.reduce<Summary>(
        (summary, business) => {
            if (
                pointInGeoJson(
                    { lat: business.latitude, lng: business.longitude },
                    polygon,
                )
            ) {
                summary.total += 1;
                summary[business.registration_status] += 1;
            }

            return summary;
        },
        { ...emptySummary },
    );
}

function markerHtml(color: string, active = false) {
    return `<span style="background:${color};box-shadow:${active ? '0 0 0 4px rgba(59,130,246,.25)' : 'none'}"></span>`;
}

function placeMarkerHtml(registered: boolean, active = false) {
    const color = registered ? '#16a34a' : '#2563eb';

    return `<span style="background:${color};box-shadow:${active ? '0 0 0 4px rgba(37,99,235,.25)' : 'none'}"></span>`;
}

function projectMarkerHtml(status: string, active = false) {
    const color = PROJECT_STATUS_COLORS[status as keyof typeof PROJECT_STATUS_COLORS] ?? '#9ca3af';

    return `<span style="background:${color};box-shadow:${active ? '0 0 0 4px rgba(124,58,237,.35)' : 'none'}"></span>`;
}

export function SectorBusinessMap({
    sectors,
    municipalities,
    businesses,
    discoveredPlaces,
    projects = [],
    selectedBusinessId = null,
    selectedPlaceId = null,
    selectedProjectId = null,
    readOnly = false,
    onBusinessSelect,
    onPlaceSelect,
    onProjectSelect,
    onDiscoverPlaces,
    onMapClick,
    onCustomPolygonChange,
}: Props) {
    const containerRef = useRef<HTMLDivElement | null>(null);
    const mapRef = useRef<LeafletMap | null>(null);
    const layersRef = useRef<L.Layer[]>([]);
    const customLayerRef = useRef<L.Layer[]>([]);
    const [customPoints, setCustomPoints] = useState<PolygonPoint[]>([]);
    const customPolygon = useMemo(
        () => buildPolygon(customPoints),
        [customPoints],
    );

    useEffect(() => {
        if (!containerRef.current || mapRef.current) {
            return;
        }

        const map = L.map(containerRef.current, {
            center: [18.4861, -69.9312],
            zoom: 12,
            zoomControl: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        map.on('click', (event: LeafletMouseEvent) => {
            if (readOnly) {
                return;
            }

            const nextPoint = {
                lat: event.latlng.lat,
                lng: event.latlng.lng,
            };
            const sectorId = resolveSectorId(nextPoint, sectors);
            const municipalityId = resolveMunicipalityId(
                nextPoint,
                municipalities,
            );

            if (event.originalEvent.shiftKey) {
                setCustomPoints((current) => [...current, nextPoint]);
                return;
            }

            onMapClick(nextPoint, sectorId, municipalityId);
        });

        mapRef.current = map;

        return () => {
            map.remove();
            mapRef.current = null;
        };
    }, [municipalities, onMapClick, readOnly, sectors]);

    useEffect(() => {
        const map = mapRef.current;
        if (!map) {
            return;
        }

        layersRef.current.forEach((layer) => layer.remove());
        layersRef.current = [];

        municipalities.forEach((municipality, index) => {
            const points = getPolygonPoints(municipality.geojson_polygon);
            if (points.length < 3) {
                return;
            }

            const layer = L.polygon(
                points.map((item) => [item.lat, item.lng]),
                {
                    color: '#64748b',
                    fillColor: index % 2 === 0 ? '#64748b' : '#475569',
                    fillOpacity: 0.06,
                    weight: 1,
                },
            )
                .bindTooltip(municipality.name)
                .addTo(map);

            layersRef.current.push(layer);
        });

        sectors.forEach((sector, index) => {
            const points = getPolygonPoints(sector.geojson_polygon);
            if (points.length < 3) {
                return;
            }

            const layer = L.polygon(
                points.map((item) => [item.lat, item.lng]),
                {
                    color: index % 2 === 0 ? '#2563eb' : '#0891b2',
                    fillColor: index % 2 === 0 ? '#2563eb' : '#0891b2',
                    fillOpacity: 0.1,
                    weight: 2,
                },
            )
                .bindTooltip(sector.name)
                .addTo(map);

            layersRef.current.push(layer);
        });

        businesses.forEach((business) => {
            const color = statusColors[business.registration_status];
            const marker = L.marker([business.latitude, business.longitude], {
                icon: L.divIcon({
                    className: 'business-map-pin',
                    html: markerHtml(color, selectedBusinessId === business.id),
                    iconSize: [22, 22],
                    iconAnchor: [11, 11],
                }),
            })
                .on('click', (event) => {
                    event.originalEvent.stopPropagation();
                    onBusinessSelect(business);
                })
                .addTo(map);

            layersRef.current.push(marker);
        });

        discoveredPlaces.forEach((place) => {
            const marker = L.marker([place.latitude, place.longitude], {
                icon: L.divIcon({
                    className: 'discovered-place-pin',
                    html: placeMarkerHtml(
                        place.registered,
                        selectedPlaceId === place.id,
                    ),
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                }),
            })
                .bindTooltip(place.title)
                .on('click', (event) => {
                    event.originalEvent.stopPropagation();
                    onPlaceSelect(place);
                })
                .addTo(map);

            layersRef.current.push(marker);
        });

        projects.forEach((project) => {
            if (
                !Number.isFinite(project.latitude) ||
                !Number.isFinite(project.longitude)
            ) {
                return;
            }

            const marker = L.marker(
                [project.latitude as number, project.longitude as number],
                {
                    icon: L.divIcon({
                        className: 'project-map-pin',
                        html: projectMarkerHtml(
                            project.status,
                            selectedProjectId === project.id,
                        ),
                        iconSize: [20, 20],
                        iconAnchor: [10, 10],
                    }),
                },
            )
                .bindTooltip(
                    `${project.name} · ${projectStatusLabel(project.status)}`,
                )
                .on('click', (event) => {
                    event.originalEvent.stopPropagation();
                    onProjectSelect?.(project);
                })
                .addTo(map);

            layersRef.current.push(marker);
        });

        const boundsPoints = [
            ...sectors.flatMap((sector) =>
                getPolygonPoints(sector.geojson_polygon),
            ),
            ...municipalities.flatMap((municipality) =>
                getPolygonPoints(municipality.geojson_polygon),
            ),
            ...businesses.map((business) => ({
                lat: business.latitude,
                lng: business.longitude,
            })),
            ...discoveredPlaces.map((place) => ({
                lat: place.latitude,
                lng: place.longitude,
            })),
            ...projects
                .filter(
                    (project) =>
                        Number.isFinite(project.latitude) &&
                        Number.isFinite(project.longitude),
                )
                .map((project) => ({
                    lat: project.latitude as number,
                    lng: project.longitude as number,
                })),
        ];

        if (boundsPoints.length) {
            map.fitBounds(
                boundsPoints.map((item) => [
                    item.lat,
                    item.lng,
                ]) as LatLngBoundsExpression,
                { padding: [28, 28], maxZoom: 15 },
            );
        }

        setTimeout(() => map.invalidateSize(), 80);
    }, [
        businesses,
        discoveredPlaces,
        municipalities,
        onBusinessSelect,
        onPlaceSelect,
        onProjectSelect,
        projects,
        sectors,
        selectedBusinessId,
        selectedPlaceId,
        selectedProjectId,
    ]);

    useEffect(() => {
        const map = mapRef.current;
        if (!map) {
            return;
        }

        customLayerRef.current.forEach((layer) => layer.remove());
        customLayerRef.current = [];

        if (customPoints.length >= 3) {
            customLayerRef.current.push(
                L.polygon(
                    customPoints.map((item) => [item.lat, item.lng]),
                    {
                        color: '#7c3aed',
                        fillColor: '#7c3aed',
                        fillOpacity: 0.14,
                        weight: 3,
                    },
                ).addTo(map),
            );
        }

        customPoints.forEach((item, index) => {
            const marker = L.marker([item.lat, item.lng], {
                draggable: true,
                icon: L.divIcon({
                    className: 'custom-polygon-vertex',
                    html: `<span>${index + 1}</span>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                }),
            })
                .on('dragend', () => {
                    const position = marker.getLatLng();
                    setCustomPoints((current) =>
                        current.map((currentPoint, pointIndex) =>
                            pointIndex === index
                                ? { lat: position.lat, lng: position.lng }
                                : currentPoint,
                        ),
                    );
                })
                .on('click', (event) => event.originalEvent.stopPropagation())
                .addTo(map);

            customLayerRef.current.push(marker);
        });
    }, [customPoints]);

    useEffect(() => {
        onCustomPolygonChange(
            customPolygon,
            summarizeBusinesses(businesses, customPolygon),
        );
    }, [businesses, customPolygon, onCustomPolygonChange]);

    return (
        <div className="flex min-h-0 flex-1 flex-col gap-3">
            {!readOnly && (
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={!customPoints.length}
                            onClick={() =>
                                setCustomPoints((current) => current.slice(0, -1))
                            }
                        >
                            <Undo2 />
                            Punto
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={!customPoints.length}
                            onClick={() => setCustomPoints([])}
                        >
                            <Trash2 />
                            Poligono
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => mapRef.current?.invalidateSize()}
                    >
                        <RotateCcw />
                        Mapa
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            const center = mapRef.current?.getCenter();

                            if (center) {
                                onDiscoverPlaces({
                                    lat: center.lat,
                                    lng: center.lng,
                                });
                            }
                        }}
                    >
                        Rastrear
                    </Button>
                </div>
            )}
            <div
                ref={containerRef}
                className="bg-muted min-h-[560px] flex-1 overflow-hidden rounded-lg border"
            />
            <style>{`
                .business-map-pin {
                    align-items: center;
                    display: flex;
                    justify-content: center;
                }

                .business-map-pin span {
                    border: 2px solid #fff;
                    border-radius: 999px;
                    display: block;
                    height: 18px;
                    width: 18px;
                }

                .project-map-pin {
                    align-items: center;
                    display: flex;
                    justify-content: center;
                }

                .project-map-pin span {
                    border: 2px solid #fff;
                    border-radius: 4px;
                    display: block;
                    height: 16px;
                    width: 16px;
                }

                .custom-polygon-vertex {
                    align-items: center;
                    background: #7c3aed;
                    border: 2px solid #fff;
                    border-radius: 999px;
                    color: #fff;
                    display: flex;
                    font-size: 12px;
                    font-weight: 700;
                    justify-content: center;
                }

                .discovered-place-pin {
                    align-items: center;
                    display: flex;
                    justify-content: center;
                }

                .discovered-place-pin span {
                    border: 2px solid #fff;
                    border-radius: 4px;
                    display: block;
                    height: 16px;
                    transform: rotate(45deg);
                    width: 16px;
                }
            `}</style>
        </div>
    );
}
