import type { DocumentType } from '@/modules/Business/constants/businessOptions';

export function normalizeDocumentNumber(type: DocumentType, value: string) {
    if (type === 1) {
        return value.replace(/\D/g, '').slice(0, 11);
    }

    if (type === 3) {
        return value.replace(/\D/g, '').slice(0, 9);
    }

    return value.replace(/[^A-Za-z0-9]/g, '').slice(0, 20);
}

export function formatDocumentNumber(type: DocumentType, value: string) {
    const normalized = normalizeDocumentNumber(type, value);

    if (type !== 1 || normalized.length <= 3) {
        return normalized;
    }

    if (normalized.length <= 10) {
        return `${normalized.slice(0, 3)}-${normalized.slice(3)}`;
    }

    return `${normalized.slice(0, 3)}-${normalized.slice(3, 10)}-${normalized.slice(10)}`;
}

export function documentNumberMaxLength(type: DocumentType) {
    return type === 1 ? 13 : type === 3 ? 9 : 20;
}
