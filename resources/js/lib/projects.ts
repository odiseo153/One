export const PROJECT_TYPES = [
    'street',
    'school',
    'park',
    'sewage',
    'public_building',
    'other',
] as const;

export type ProjectType = (typeof PROJECT_TYPES)[number];

export const PROJECT_STATUSES = [
    'planned',
    'in_progress',
    'paused',
    'completed',
    'cancelled',
] as const;

export type ProjectStatus = (typeof PROJECT_STATUSES)[number];

export const TYPE_LABELS: Record<ProjectType, string> = {
    street: 'Calle / vía',
    school: 'Escuela',
    park: 'Parque',
    sewage: 'Alcantarillado',
    public_building: 'Edificio público',
    other: 'Otra',
};

export const STATUS_LABELS: Record<ProjectStatus, string> = {
    planned: 'Planificada',
    in_progress: 'En ejecución',
    paused: 'Pausada',
    completed: 'Completada',
    cancelled: 'Cancelada',
};

export const PROJECT_ROLES = ['manager', 'supervisor', 'inspector', 'collaborator'] as const;

export type ProjectRole = (typeof PROJECT_ROLES)[number];

export const ROLE_LABELS: Record<ProjectRole, string> = {
    manager: 'Encargado',
    supervisor: 'Supervisor',
    inspector: 'Inspector',
    collaborator: 'Colaborador',
};

/** Colores de marcador en el mapa según el estado de la obra. */
export const PROJECT_STATUS_COLORS: Record<ProjectStatus, string> = {
    planned: '#9ca3af',
    in_progress: '#f59e0b',
    paused: '#ef4444',
    completed: '#22c55e',
    cancelled: '#1f2937',
};

export function projectTypeLabel(type: string): string {
    return TYPE_LABELS[type as ProjectType] ?? type;
}

export function projectStatusLabel(status: string): string {
    return STATUS_LABELS[status as ProjectStatus] ?? status;
}

export function projectRoleLabel(role: string): string {
    return ROLE_LABELS[role as ProjectRole] ?? role;
}

export function statusBadgeClass(status: string): string {
    switch (status) {
        case 'in_progress':
            return 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'paused':
            return 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400';
        case 'completed':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'cancelled':
            return 'border-zinc-500/30 bg-zinc-500/10 text-zinc-700 dark:text-zinc-400';
        default:
            return 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-400';
    }
}

const currencyFormatter = new Intl.NumberFormat('es-DO', {
    style: 'currency',
    currency: 'DOP',
    maximumFractionDigits: 0,
});

export function formatMoney(value: number | string | null | undefined): string {
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return '—';
    }

    return currencyFormatter.format(numeric);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('es-DO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-DO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function progressColorClass(value: number): string {
    if (value >= 100) {
        return 'bg-emerald-500';
    }
    if (value >= 60) {
        return 'bg-sky-500';
    }
    if (value >= 30) {
        return 'bg-amber-500';
    }
    if (value > 0) {
        return 'bg-amber-400';
    }

    return 'bg-muted-foreground/30';
}
