<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;
use Illuminate\Validation\ValidationException;

class UpdateBusinessStatusService
{
    public function __construct(
        private readonly BusinessRepository $repository,
        private readonly BusinessPayloadService $payloadService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Business $business, string $status, ?int $municipalityId): array
    {
        abort_unless(! $municipalityId || $business->municipality_id === $municipalityId, 404);

        if (
            $status === BusinessStatusService::REGISTERED
            && (! $business->rnc || (! $business->primary_activity && ! $business->primary_ciiu_id))
        ) {
            throw ValidationException::withMessages([
                'registration_status' => 'Completa el RNC y la actividad principal antes de registrar el negocio.',
            ]);
        }

        $updated = $this->repository->update($business->id, [
            'registration_status' => $status,
            'last_verified_at' => now(),
        ]);

        return $this->payloadService->execute($updated);
    }
}
