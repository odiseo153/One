import { Head, useForm } from '@inertiajs/react';
import { HardHat, MapPinned, Plus, Store } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { BusinessFormDialog } from '@/modules/Business/components/BusinessFormDialog';
import { BusinessDetails } from '@/modules/Business/components/BusinessDetails';
import { PlaceDetails } from '@/modules/Business/components/PlaceDetails';
import { ProjectMapDetails } from '@/modules/Business/components/ProjectMapDetails';
import {
    type BusinessMapRecord,
    type DiscoveredPlaceRecord,
    type GeoJsonPolygon,
    type MunicipalityMapRecord,
    type ProjectMapRecord,
    type RegistrationStatus,
    SectorBusinessMap,
} from '@/modules/Business/components/SectorBusinessMap';
import { businessService } from '@/modules/Business/services/businessService';
import { validateBusinessForm } from '@/modules/Business/helpers/businessFormValidation';
import { formatDocumentNumber } from '@/modules/Business/helpers/documentNumber';
import type { BusinessFormData } from '@/modules/Business/types/businessForm';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    PROJECT_STATUSES,
    projectStatusLabel,
    PROJECT_STATUS_COLORS,
} from '@/modules/Project/helpers/projectFormatting';

type SectorRecord = {
    id: number;
    municipality_id: number;
    name: string;
    geojson_polygon: GeoJsonPolygon | null;
};

type ProvinceRecord = {
    id: number;
    name: string;
};

type MunicipalityRecord = {
    id: number;
    name: string;
    province_id: number | null;
    geojson_polygon: GeoJsonPolygon | null;
};

type Summary = Record<RegistrationStatus | 'total', number>;

type Props = {
    municipality: { id: number; name: string } | null;
    provinces: ProvinceRecord[];
    municipalities: MunicipalityRecord[];
    sectors: SectorRecord[];
    businesses: BusinessMapRecord[];
    projects: ProjectMapRecord[];
    inspectors: { id: number; name: string }[];
    summary: Summary;
    userSectorId: number | null;
    filters: {
        province_id?: string | null;
        municipality_id?: string | null;
    };
};

const statusLabels: Record<RegistrationStatus, string> = {
    registered: 'Registrado',
    unregistered: 'No registrado',
    pending_verification: 'Pendiente',
};

const emptySummary: Summary = {
    total: 0,
    registered: 0,
    unregistered: 0,
    pending_verification: 0,
};

function initialFilterValue(key: string) {
    if (typeof window === 'undefined') {
        return 'all';
    }

    return (
        new URLSearchParams(window.location.search).get(`filter[${key}]`) ??
        'all'
    );
}

function todayDate() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 10);
}

export default function SectorMap({
    municipality,
    provinces,
    municipalities,
    sectors,
    businesses,
    projects,
    inspectors,
    userSectorId,
    filters,
}: Props) {
    const [items, setItems] = useState(businesses);
    const [selectedBusiness, setSelectedBusiness] =
        useState<BusinessMapRecord | null>(businesses[0] ?? null);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<BusinessMapRecord | null>(null);
    const [customSummary, setCustomSummary] = useState<Summary>(emptySummary);
    const [customPolygon, setCustomPolygon] = useState<GeoJsonPolygon | null>(
        null,
    );
    const [discoveredPlaces, setDiscoveredPlaces] = useState<
        DiscoveredPlaceRecord[]
    >([]);
    const [selectedPlace, setSelectedPlace] =
        useState<DiscoveredPlaceRecord | null>(null);
    const [selectedProject, setSelectedProject] =
        useState<ProjectMapRecord | null>(projects[0] ?? null);
    const [projectStatusFilter, setProjectStatusFilter] = useState('all');
    const [discoverQuery, setDiscoverQuery] = useState(
        'negocios supermercados colmados',
    );
    const [discovering, setDiscovering] = useState(false);
    const [statusFilter, setStatusFilter] = useState(() =>
        initialFilterValue('registration_status'),
    );
    const [sectorFilter, setSectorFilter] = useState(() =>
        initialFilterValue('sector_id'),
    );
    const [provinceFilter, setProvinceFilter] = useState(
        filters.province_id ?? 'all',
    );
    const [municipalityFilter, setMunicipalityFilter] = useState(
        filters.municipality_id ?? 'all',
    );

    useEffect(() => {
        setItems(businesses);
        setSelectedBusiness((current) => {
            if (!current) {
                return businesses[0] ?? null;
            }

            return (
                businesses.find((business) => business.id === current.id) ??
                businesses[0] ??
                null
            );
        });
    }, [businesses]);

    useEffect(() => {
        setSelectedProject((current) => {
            if (!current) {
                return projects[0] ?? null;
            }

            return (
                projects.find((project) => project.id === current.id) ??
                projects[0] ??
                null
            );
        });
    }, [projects]);

    const filteredProjects = useMemo(
        () =>
            projects.filter((project) => {
                if (
                    projectStatusFilter !== 'all' &&
                    project.status !== projectStatusFilter
                ) {
                    return false;
                }

                return (
                    sectorFilter === 'all' ||
                    project.sector_id === Number(sectorFilter)
                );
            }),
        [projectStatusFilter, projects, sectorFilter],
    );

    const filteredItems = useMemo(
        () =>
            items.filter((item) => {
                if (
                    statusFilter !== 'all' &&
                    item.registration_status !== statusFilter
                ) {
                    return false;
                }

                return (
                    sectorFilter === 'all' ||
                    item.sector_id === Number(sectorFilter)
                );
            }),
        [items, sectorFilter, statusFilter],
    );

    const visibleSummary = useMemo(
        () =>
            filteredItems.reduce<Summary>(
                (current, item) => {
                    current.total += 1;
                    current[item.registration_status] += 1;

                    return current;
                },
                { ...emptySummary },
            ),
        [filteredItems],
    );

    const form = useForm<BusinessFormData>({
        municipality_id: '',
        sector_id: 'none',
        name: '',
        category: '',
        latitude: '',
        longitude: '',
        address_text: '',
        registration_status: 'pending_verification' as RegistrationStatus,
        is_registered: false,
        property_status: 2,
        document_type: 1,
        document_number: '',
        rnc: '',
        primary_activity: '',
        secondary_activity: '',
        photo: null,
        detected_at: todayDate(),
        last_verified_at: '',
        inspector_id: 'none',
        employees: [],
    });

    const applyFilters = (next: {
        province?: string;
        municipality?: string;
        sector?: string;
        status?: string;
    }) => {
        const nextProvince = next.province ?? provinceFilter;
        const nextMunicipality = next.municipality ?? municipalityFilter;
        const nextSector = next.sector ?? sectorFilter;
        const nextStatus = next.status ?? statusFilter;

        setProvinceFilter(nextProvince);
        setMunicipalityFilter(nextMunicipality);
        setSectorFilter(nextSector);
        setStatusFilter(nextStatus);

        businessService.filterMap({
            ...(nextProvince !== 'all' ? { province_id: nextProvince } : {}),
            ...(nextMunicipality !== 'all'
                ? { municipality_id: nextMunicipality }
                : {}),
            ...(nextSector !== 'all' ? { sector_id: nextSector } : {}),
            ...(nextStatus !== 'all'
                ? { registration_status: nextStatus }
                : {}),
        });
    };

    const syncStatus = async (
        business: BusinessMapRecord,
        registrationStatus: RegistrationStatus,
    ) => {
        const updatedBusiness = await businessService.updateStatus(
            business.id,
            registrationStatus,
        );

        if (!updatedBusiness) {
            return;
        }
        setItems((current) =>
            current.map((item) =>
                item.id === updatedBusiness.id ? updatedBusiness : item,
            ),
        );
        setSelectedBusiness(updatedBusiness);
    };

    const selectedFormMunicipalityId = form.data.municipality_id
        ? Number(form.data.municipality_id)
        : null;

    const availableFormSectors = useMemo(
        () =>
            sectors.filter((sector) => {
                if (userSectorId && sector.id !== userSectorId) {
                    return false;
                }

                return (
                    !selectedFormMunicipalityId ||
                    sector.municipality_id === selectedFormMunicipalityId
                );
            }),
        [sectors, selectedFormMunicipalityId, userSectorId],
    );

    const openCreate = (
        position?: { lat: number; lng: number },
        sectorId?: number | null,
        municipalityId?: number | null,
        place?: DiscoveredPlaceRecord | null,
    ) => {
        const sector = sectors.find((item) => item.id === sectorId);
        const nextMunicipalityId =
            sector?.municipality_id ?? municipalityId ?? null;

        setEditing(null);
        form.setData({
            municipality_id: nextMunicipalityId
                ? String(nextMunicipalityId)
                : '',
            sector_id: userSectorId
                ? String(userSectorId)
                : sectorId
                  ? String(sectorId)
                  : 'none',
            name: place?.title ?? '',
            category: place?.category ?? '',
            latitude: position ? String(position.lat.toFixed(7)) : '',
            longitude: position ? String(position.lng.toFixed(7)) : '',
            address_text: place?.address ?? '',
            registration_status: 'pending_verification',
            is_registered: false,
            property_status: 2,
            document_type: 1,
            document_number: '',
            rnc: '',
            primary_activity: '',
            secondary_activity: '',
            photo: null,
            detected_at: todayDate(),
            last_verified_at: '',
            inspector_id: 'none',
            employees: [],
        });
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (business: BusinessMapRecord) => {
        setEditing(business);
        form.setData({
            municipality_id: String(business.municipality_id),
            sector_id: business.sector_id ? String(business.sector_id) : 'none',
            name: business.name,
            category: business.category ?? '',
            latitude: String(business.latitude),
            longitude: String(business.longitude),
            address_text: business.address_text ?? '',
            registration_status: business.registration_status,
            is_registered: business.is_registered,
            property_status: business.property_status ?? 2,
            document_type: business.document_type ?? 1,
            document_number: formatDocumentNumber(
                business.document_type ?? 1,
                business.document_number ?? '',
            ),
            rnc: business.rnc ?? '',
            primary_activity: business.primary_activity ?? '',
            secondary_activity: business.secondary_activity ?? '',
            photo: null,
            detected_at: business.detected_at ?? '',
            last_verified_at: business.last_verified_at ?? '',
            inspector_id: business.inspector_id
                ? String(business.inspector_id)
                : 'none',
            employees: business.employees.map((employee) => ({
                ...employee,
                document_number: formatDocumentNumber(
                    employee.document_type,
                    employee.document_number,
                ),
            })),
        });
        form.clearErrors();
        setOpen(true);
    };

    const handleMapClick = useCallback(
        (
            position: { lat: number; lng: number },
            sectorId: number | null,
            municipalityId: number | null,
        ) => {
            openCreate(position, sectorId, municipalityId);
        },
        [sectors, userSectorId],
    );

    const discoverPlaces = async (position: { lat: number; lng: number }) => {
        setDiscovering(true);

        const params = new URLSearchParams({
            latitude: String(position.lat),
            longitude: String(position.lng),
            query: discoverQuery,
        });

        if (sectorFilter !== 'all') {
            params.set('sector_id', sectorFilter);
        }

        if (municipalityFilter !== 'all') {
            params.set('municipality_id', municipalityFilter);
        }

        const places = await businessService.discoverPlaces(params);
        setDiscovering(false);
        setDiscoveredPlaces(places);
        setSelectedPlace(places[0] ?? null);
    };

    const selectDiscoveredPlace = (place: DiscoveredPlaceRecord) => {
        setSelectedPlace(place);

        if (place.business_id) {
            const business = items.find(
                (item) => item.id === place.business_id,
            );

            if (business) {
                setSelectedBusiness(business);
            }
        }
    };

    const createFromPlace = (place: DiscoveredPlaceRecord) => {
        openCreate(
            { lat: place.latitude, lng: place.longitude },
            place.sector_id,
            place.municipality_id,
            place,
        );
    };

    const handleCustomPolygonChange = useCallback(
        (polygon: GeoJsonPolygon | null, nextSummary: Summary) => {
            setCustomPolygon(polygon);
            setCustomSummary(nextSummary);
        },
        [],
    );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const errors = validateBusinessForm(form.data);
        form.clearErrors();

        if (Object.keys(errors).length > 0) {
            form.setError(errors);
            return;
        }

        businessService.save(form, editing?.id ?? null, () => setOpen(false));
    };

    return (
        <>
            <Head title="Mapa sectorial" />
            <div className="flex min-h-[calc(100vh-2rem)] flex-1 flex-col gap-5 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Mapa sectorial"
                        description={
                            municipality?.name ??
                            'Vista general de sectores y locales'
                        }
                    />
                    <Button onClick={() => openCreate()}>
                        <Plus />
                        Local
                    </Button>
                </div>

                <div className="grid gap-3 md:grid-cols-4">
                    <Metric label="Locales" value={visibleSummary.total} />
                    <Metric
                        label="Registrados"
                        value={visibleSummary.registered}
                    />
                    <Metric
                        label="No registrados"
                        value={visibleSummary.unregistered}
                    />
                    <Metric
                        label="Pendientes"
                        value={visibleSummary.pending_verification}
                    />
                </div>

                <div className="grid min-h-0 flex-1 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <section className="bg-background flex min-h-[640px] flex-col rounded-lg border p-3">
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <Select
                                    value={provinceFilter}
                                    onValueChange={(value) =>
                                        applyFilters({
                                            province: value,
                                            municipality: 'all',
                                            sector: 'all',
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-[210px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas las provincias
                                        </SelectItem>
                                        {provinces.map((province) => (
                                            <SelectItem
                                                key={province.id}
                                                value={String(province.id)}
                                            >
                                                {province.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={municipalityFilter}
                                    onValueChange={(value) =>
                                        applyFilters({
                                            municipality: value,
                                            sector: 'all',
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-[210px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los municipios
                                        </SelectItem>
                                        {municipalities.map(
                                            (municipalityOption) => (
                                                <SelectItem
                                                    key={municipalityOption.id}
                                                    value={String(
                                                        municipalityOption.id,
                                                    )}
                                                >
                                                    {municipalityOption.name}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={sectorFilter}
                                    onValueChange={(value) =>
                                        applyFilters({ sector: value })
                                    }
                                >
                                    <SelectTrigger className="w-[210px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas las zonas
                                        </SelectItem>
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
                                <Select
                                    value={statusFilter}
                                    onValueChange={(value) =>
                                        applyFilters({ status: value })
                                    }
                                >
                                    <SelectTrigger className="w-[190px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los estados
                                        </SelectItem>
                                        {Object.entries(statusLabels).map(
                                            ([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={projectStatusFilter}
                                    onValueChange={setProjectStatusFilter}
                                >
                                    <SelectTrigger className="w-[170px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Obras: todos
                                        </SelectItem>
                                        {PROJECT_STATUSES.map((status) => (
                                            <SelectItem
                                                key={status}
                                                value={status}
                                            >
                                                Obras:{' '}
                                                {projectStatusLabel(status)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <span className="text-muted-foreground text-sm">
                                Shift + clic dibuja una zona
                            </span>
                        </div>
                        <SectorBusinessMap
                            sectors={sectors}
                            municipalities={
                                municipalities as MunicipalityMapRecord[]
                            }
                            businesses={filteredItems}
                            projects={filteredProjects}
                            discoveredPlaces={discoveredPlaces}
                            selectedBusinessId={selectedBusiness?.id ?? null}
                            selectedPlaceId={selectedPlace?.id ?? null}
                            selectedProjectId={selectedProject?.id ?? null}
                            onBusinessSelect={setSelectedBusiness}
                            onPlaceSelect={selectDiscoveredPlace}
                            onProjectSelect={setSelectedProject}
                            onDiscoverPlaces={(point) =>
                                void discoverPlaces(point)
                            }
                            onMapClick={handleMapClick}
                            onCustomPolygonChange={handleCustomPolygonChange}
                        />
                    </section>

                    <aside className="flex min-h-0 flex-col gap-4">
                        <section className="bg-background rounded-lg border p-4">
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <HardHat className="size-4" />
                                    <h2 className="text-sm font-semibold">
                                        Proyectos
                                    </h2>
                                </div>
                                <span className="text-muted-foreground text-xs">
                                    {filteredProjects.length}
                                </span>
                            </div>

                            {filteredProjects.length > 0 ? (
                                <div className="max-h-48 space-y-1 overflow-y-auto pr-1">
                                    {filteredProjects.map((project) => {
                                        const color =
                                            PROJECT_STATUS_COLORS[
                                                project.status as keyof typeof PROJECT_STATUS_COLORS
                                            ] ?? '#9ca3af';

                                        return (
                                            <button
                                                key={project.id}
                                                type="button"
                                                onClick={() =>
                                                    setSelectedProject(project)
                                                }
                                                className={`flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors ${
                                                    selectedProject?.id ===
                                                    project.id
                                                        ? 'bg-muted'
                                                        : 'hover:bg-muted/50'
                                                }`}
                                            >
                                                <span
                                                    className="inline-block size-2.5 shrink-0 rounded-sm"
                                                    style={{
                                                        background: color,
                                                    }}
                                                />
                                                <span className="min-w-0 flex-1 truncate">
                                                    {project.name}
                                                </span>
                                                <span className="text-muted-foreground text-xs tabular-nums">
                                                    {
                                                        project.progress_percentage
                                                    }
                                                    %
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    No hay obras en el mapa.
                                </p>
                            )}

                            {selectedProject ? (
                                <ProjectMapDetails
                                    project={selectedProject}
                                    onOpen={() =>
                                        businessService.openProject(
                                            selectedProject.id,
                                        )
                                    }
                                />
                            ) : null}
                        </section>

                        <section className="bg-background rounded-lg border p-4">
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <MapPinned className="size-4" />
                                    <h2 className="text-sm font-semibold">
                                        Rastreo externo
                                    </h2>
                                </div>
                                <span className="text-muted-foreground text-xs">
                                    {discoveredPlaces.length}
                                </span>
                            </div>
                            <Input
                                value={discoverQuery}
                                onChange={(event) =>
                                    setDiscoverQuery(event.target.value)
                                }
                                placeholder="Buscar en Google Maps..."
                            />
                            <p className="text-muted-foreground mt-2 text-xs">
                                {discovering
                                    ? 'Consultando SerpApi...'
                                    : 'Usa Rastrear en el mapa para consultar la zona visible.'}
                            </p>
                            {selectedPlace ? (
                                <PlaceDetails
                                    place={selectedPlace}
                                    onCreate={() =>
                                        createFromPlace(selectedPlace)
                                    }
                                />
                            ) : null}
                        </section>

                        <section className="bg-background rounded-lg border p-4">
                            <div className="mb-3 flex items-center gap-2">
                                <MapPinned className="size-4" />
                                <h2 className="text-sm font-semibold">
                                    Zona dibujada
                                </h2>
                            </div>
                            <div className="grid grid-cols-2 gap-2 text-sm">
                                <Metric
                                    compact
                                    label="Total"
                                    value={customSummary.total}
                                />
                                <Metric
                                    compact
                                    label="Registrados"
                                    value={customSummary.registered}
                                />
                                <Metric
                                    compact
                                    label="No registrados"
                                    value={customSummary.unregistered}
                                />
                                <Metric
                                    compact
                                    label="Pendientes"
                                    value={customSummary.pending_verification}
                                />
                            </div>
                            {customPolygon ? (
                                <Textarea
                                    readOnly
                                    value={JSON.stringify(customPolygon)}
                                    className="mt-3 h-20 resize-none font-mono text-xs"
                                />
                            ) : null}
                        </section>

                        <section className="bg-background min-h-0 rounded-lg border p-4">
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <Store className="size-4" />
                                    <h2 className="text-sm font-semibold">
                                        Local
                                    </h2>
                                </div>
                                {selectedBusiness ? (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            openEdit(selectedBusiness)
                                        }
                                    >
                                        Editar
                                    </Button>
                                ) : null}
                            </div>
                            {selectedBusiness ? (
                                <BusinessDetails
                                    business={selectedBusiness}
                                    onStatusChange={(status) =>
                                        void syncStatus(
                                            selectedBusiness,
                                            status,
                                        )
                                    }
                                />
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    Selecciona un pin.
                                </p>
                            )}
                        </section>
                    </aside>
                </div>

                <BusinessFormDialog
                    open={open}
                    onOpenChange={setOpen}
                    editing={editing}
                    form={form}
                    sectors={availableFormSectors}
                    inspectors={inspectors}
                    onSubmit={submit}
                />
            </div>
        </>
    );
}

function Metric({
    label,
    value,
    compact = false,
}: {
    label: string;
    value: number;
    compact?: boolean;
}) {
    return (
        <div
            className={
                compact
                    ? 'rounded-md border px-3 py-2'
                    : 'bg-background rounded-lg border px-4 py-3'
            }
        >
            <p className="text-muted-foreground text-xs font-medium">{label}</p>
            <p
                className={
                    compact ? 'text-lg font-semibold' : 'text-2xl font-semibold'
                }
            >
                {value}
            </p>
        </div>
    );
}
