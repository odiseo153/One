import { Plus, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DOCUMENT_TYPES,
    type DocumentType,
} from '@/modules/Business/constants/businessOptions';
import {
    documentNumberMaxLength,
    formatDocumentNumber,
} from '@/modules/Business/helpers/documentNumber';
import type { BusinessEmployeeFormData } from '@/modules/Business/types/businessForm';

type Props = {
    employees: BusinessEmployeeFormData[];
    errors: Record<string, string>;
    onChange: (employees: BusinessEmployeeFormData[]) => void;
};

const emptyEmployee = (): BusinessEmployeeFormData => ({
    first_name: '',
    last_name: '',
    document_type: 1,
    document_number: '',
    salary: '',
});

export function BusinessEmployeesFields({
    employees,
    errors,
    onChange,
}: Props) {
    const update = <K extends keyof BusinessEmployeeFormData>(
        index: number,
        field: K,
        value: BusinessEmployeeFormData[K],
    ) => {
        onChange(
            employees.map((employee, employeeIndex) =>
                employeeIndex === index
                    ? { ...employee, [field]: value }
                    : employee,
            ),
        );
    };

    return (
        <section className="space-y-3 md:col-span-2">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <Label>Empleados</Label>
                    <p className="text-muted-foreground text-xs">Opcional</p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onChange([...employees, emptyEmployee()])}
                >
                    <Plus />
                    Agregar empleado
                </Button>
            </div>

            {employees.map((employee, index) => (
                <div
                    key={employee.id ?? index}
                    className="grid gap-3 rounded-lg border p-3 md:grid-cols-2"
                >
                    <EmployeeField
                        label="Nombre"
                        error={errors[`employees.${index}.first_name`]}
                    >
                        <Input
                            value={employee.first_name}
                            onChange={(event) =>
                                update(index, 'first_name', event.target.value)
                            }
                        />
                    </EmployeeField>
                    <EmployeeField
                        label="Apellido"
                        error={errors[`employees.${index}.last_name`]}
                    >
                        <Input
                            value={employee.last_name}
                            onChange={(event) =>
                                update(index, 'last_name', event.target.value)
                            }
                        />
                    </EmployeeField>
                    <EmployeeField
                        label="Tipo de documento"
                        error={errors[`employees.${index}.document_type`]}
                    >
                        <Select
                            value={String(employee.document_type)}
                            onValueChange={(value) => {
                                const type = Number(value) as DocumentType;
                                onChange(
                                    employees.map(
                                        (currentEmployee, employeeIndex) =>
                                            employeeIndex === index
                                                ? {
                                                      ...currentEmployee,
                                                      document_type: type,
                                                      document_number:
                                                          formatDocumentNumber(
                                                              type,
                                                              currentEmployee.document_number,
                                                          ),
                                                  }
                                                : currentEmployee,
                                    ),
                                );
                            }}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {DOCUMENT_TYPES.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={String(option.value)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </EmployeeField>
                    <EmployeeField
                        label="Número de documento"
                        error={errors[`employees.${index}.document_number`]}
                    >
                        <Input
                            inputMode={
                                employee.document_type === 2
                                    ? 'text'
                                    : 'numeric'
                            }
                            maxLength={documentNumberMaxLength(
                                employee.document_type,
                            )}
                            value={employee.document_number}
                            onChange={(event) =>
                                update(
                                    index,
                                    'document_number',
                                    formatDocumentNumber(
                                        employee.document_type,
                                        event.target.value,
                                    ),
                                )
                            }
                        />
                    </EmployeeField>
                    <EmployeeField
                        label="Sueldo"
                        error={errors[`employees.${index}.salary`]}
                    >
                        <Input
                            type="number"
                            min="0"
                            step="0.01"
                            value={employee.salary}
                            onChange={(event) =>
                                update(index, 'salary', event.target.value)
                            }
                        />
                    </EmployeeField>
                    <div className="flex items-end justify-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                onChange(
                                    employees.filter(
                                        (_, employeeIndex) =>
                                            employeeIndex !== index,
                                    ),
                                )
                            }
                        >
                            <Trash2 />
                            Quitar
                        </Button>
                    </div>
                </div>
            ))}
        </section>
    );
}

function EmployeeField({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
