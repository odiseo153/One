<?php

namespace App\Modules\Business\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Modules\Business\Domain\Enums\DocumentType;
use App\Modules\Business\Domain\Enums\PropertyStatus;
use App\Modules\Business\Domain\Services\BusinessStatusService;
use Closure;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('is_registered')) {
            $this->merge([
                'registration_status' => $this->boolean('is_registered')
                    ? BusinessStatusService::REGISTERED
                    : BusinessStatusService::UNREGISTERED,
            ]);
        }

        $documentType = DocumentType::tryFrom((int) $this->input('document_type'));
        $employeesInput = $this->input('employees', []);
        $employees = [];

        if (is_array($employeesInput)) {
            foreach ($employeesInput as $employee) {
                if (is_array($employee)) {
                    $type = DocumentType::tryFrom((int) ($employee['document_type'] ?? 0));
                    $employee['document_number'] = $this->normalizeDocumentNumber(
                        $type,
                        (string) ($employee['document_number'] ?? ''),
                    );
                }

                $employees[] = $employee;
            }
        }

        $this->merge([
            'document_number' => $this->normalizeDocumentNumber(
                $documentType,
                (string) $this->input('document_number', ''),
            ),
            'employees' => $employees,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'is_registered' => ['required', 'boolean'],
            'registration_status' => ['required', Rule::in(BusinessStatusService::STATUSES)],
            'property_status' => ['required', Rule::enum(PropertyStatus::class)],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', $this->documentNumberRule()],
            'rnc' => ['nullable', 'required_if:is_registered,true', 'regex:/^\d{9}$/'],
            'primary_activity' => ['nullable', 'required_if:is_registered,true', 'string', 'max:255'],
            'secondary_activity' => ['nullable', 'string', 'max:255'],
            'primary_ciiu_id' => ['nullable', 'integer', 'exists:business_categories,id'],
            'secondary_ciiu_id' => ['nullable', 'integer', 'different:primary_ciiu_id', 'exists:business_categories,id'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'detected_at' => ['nullable', 'date'],
            'last_verified_at' => ['nullable', 'date'],
            'inspector_id' => ['nullable', 'integer', 'exists:users,id'],
            'employees' => ['nullable', 'array'],
            'employees.*.first_name' => ['required', 'string', 'max:255'],
            'employees.*.last_name' => ['required', 'string', 'max:255'],
            'employees.*.document_type' => ['required', Rule::enum(DocumentType::class)],
            'employees.*.document_number' => ['required', 'string', $this->documentNumberRule()],
            'employees.*.salary' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'rnc.required_if' => 'El RNC es obligatorio para negocios registrados.',
            'rnc.regex' => 'El RNC debe contener exactamente 9 dígitos.',
            'primary_activity.required_if' => 'La actividad principal es obligatoria para negocios registrados.',
            'secondary_ciiu_id.different' => 'La actividad CIIU secundaria debe ser diferente de la principal.',
        ];
    }

    private function documentNumberRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $typeAttribute = str_replace('document_number', 'document_type', $attribute);
            $type = DocumentType::tryFrom((int) $this->input($typeAttribute));

            $valid = match ($type) {
                DocumentType::Cedula => preg_match('/^\d{11}$/', (string) $value) === 1,
                DocumentType::Rnc => preg_match('/^\d{9}$/', (string) $value) === 1,
                DocumentType::Passport => preg_match('/^[A-Za-z0-9]{6,20}$/', (string) $value) === 1,
                null => true,
            };

            if (! $valid) {
                $fail(match ($type) {
                    DocumentType::Cedula => 'La cédula debe contener exactamente 11 dígitos.',
                    DocumentType::Rnc => 'El RNC debe contener exactamente 9 dígitos.',
                    default => 'El pasaporte debe contener entre 6 y 20 caracteres alfanuméricos.',
                });
            }
        };
    }

    private function normalizeDocumentNumber(?DocumentType $type, string $value): string
    {
        if (in_array($type, [DocumentType::Cedula, DocumentType::Rnc], true)) {
            return preg_replace('/\D/', '', $value) ?? '';
        }

        return $value;
    }
}
