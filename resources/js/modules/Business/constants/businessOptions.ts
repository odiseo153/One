export const DOCUMENT_TYPES = [
    { value: 1, label: 'Cédula' },
    { value: 2, label: 'Pasaporte' },
    { value: 3, label: 'RNC' },
] as const;

export const PROPERTY_STATUSES = [
    { value: 1, label: 'Alquilada' },
    { value: 2, label: 'Propia' },
    { value: 3, label: 'Abandonada' },
    { value: 4, label: 'Remodelación' },
    { value: 5, label: 'Construcción' },
] as const;

export type DocumentType = (typeof DOCUMENT_TYPES)[number]['value'];
export type PropertyStatus = (typeof PROPERTY_STATUSES)[number]['value'];
