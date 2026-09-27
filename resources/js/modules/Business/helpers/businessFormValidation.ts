import type {
    BusinessEmployeeFormData,
    BusinessFormData,
} from '@/modules/Business/types/businessForm';
import type { DocumentType } from '@/modules/Business/constants/businessOptions';
import { normalizeDocumentNumber } from '@/modules/Business/helpers/documentNumber';

function documentError(type: DocumentType, value: string) {
    const normalized = normalizeDocumentNumber(type, value);

    if (type === 1 && !/^\d{11}$/.test(normalized)) {
        return 'La cédula debe contener exactamente 11 dígitos.';
    }

    if (type === 3 && !/^\d{9}$/.test(normalized)) {
        return 'El RNC debe contener exactamente 9 dígitos.';
    }

    if (type === 2 && !/^[A-Za-z0-9]{6,20}$/.test(normalized)) {
        return 'El pasaporte debe contener entre 6 y 20 caracteres alfanuméricos.';
    }

    return null;
}

function validateEmployee(
    employee: BusinessEmployeeFormData,
    index: number,
    errors: Record<string, string>,
) {
    if (!employee.first_name.trim()) {
        errors[`employees.${index}.first_name`] = 'El nombre es obligatorio.';
    }
    if (!employee.last_name.trim()) {
        errors[`employees.${index}.last_name`] = 'El apellido es obligatorio.';
    }
    if (!employee.document_number.trim()) {
        errors[`employees.${index}.document_number`] =
            'El número de documento es obligatorio.';
    } else {
        const error = documentError(
            employee.document_type,
            employee.document_number,
        );
        if (error) {
            errors[`employees.${index}.document_number`] = error;
        }
    }
    if (employee.salary === '' || Number(employee.salary) < 0) {
        errors[`employees.${index}.salary`] = 'El sueldo debe ser válido.';
    }
}

export function validateBusinessForm(values: BusinessFormData) {
    const errors: Record<string, string> = {};

    if (values.is_registered) {
        if (!/^\d{9}$/.test(values.rnc)) {
            errors.rnc = 'El RNC debe contener exactamente 9 dígitos.';
        }
        if (!values.primary_activity.trim()) {
            errors.primary_activity = 'La actividad principal es obligatoria.';
        }
    }

    if (!values.document_number) {
        errors.document_number = 'Ingresa el número de documento.';
    } else {
        const error = documentError(
            values.document_type,
            values.document_number,
        );
        if (error) {
            errors.document_number = error;
        }
    }

    values.employees.forEach((employee, index) =>
        validateEmployee(employee, index, errors),
    );

    return errors;
}
