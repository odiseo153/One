<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Models\Complaint;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;

class ManageComplaintsService
{
    private const TRANSITIONS = [
        Complaint::STATUS_RECEIVED => [Complaint::STATUS_IN_PROGRESS, Complaint::STATUS_RESOLVED],
        Complaint::STATUS_IN_PROGRESS => [Complaint::STATUS_RECEIVED, Complaint::STATUS_RESOLVED],
        Complaint::STATUS_RESOLVED => [Complaint::STATUS_IN_PROGRESS],
    ];

    public function __construct(private readonly ComplaintRepository $repository) {}

    public function index(?int $municipalityId, array $filters): array
    {
        $data = $this->repository->getManagementData($municipalityId, $filters);

        return [
            ...$data,
            'categories' => $this->categories(),
            'statuses' => $this->statuses(),
            'stats' => $this->stats($municipalityId),
        ];
    }

    public function find(?int $municipalityId, int $id): Complaint
    {
        return $this->repository->findManaged($municipalityId, $id);
    }

    public function users(?int $municipalityId): array
    {
        return $this->repository->getUserOptions($municipalityId);
    }

    public function assign(?int $municipalityId, int $id, ?int $userId): void
    {
        $this->repository->assign($municipalityId, $id, $userId);
    }

    public function updateStatus(?int $municipalityId, int $id, string $status, ?int $userId, ?string $note): void
    {
        $complaint = $this->find($municipalityId, $id);
        abort_unless(in_array($status, self::TRANSITIONS[$complaint->status] ?? [], true), 422, __('Estado inválido.'));
        $this->repository->changeStatus($municipalityId, $id, $status, $userId, $note);
    }

    public function categories(): array
    {
        return [
            ['value' => Complaint::CATEGORY_BACHE, 'label' => 'Bache'],
            ['value' => Complaint::CATEGORY_ALUMBRADO, 'label' => 'Alumbrado'],
            ['value' => Complaint::CATEGORY_BASURA, 'label' => 'Basura'],
            ['value' => Complaint::CATEGORY_AGUA, 'label' => 'Agua'],
            ['value' => Complaint::CATEGORY_OTRO, 'label' => 'Otro'],
        ];
    }

    public function statuses(): array
    {
        return [
            ['value' => Complaint::STATUS_RECEIVED, 'label' => 'Recibida'],
            ['value' => Complaint::STATUS_IN_PROGRESS, 'label' => 'En proceso'],
            ['value' => Complaint::STATUS_RESOLVED, 'label' => 'Resuelta'],
        ];
    }

    private function stats(?int $municipalityId): array
    {
        $complaints = $this->repository->getStatsRows($municipalityId);
        $byStatus = collect($this->statuses())->mapWithKeys(fn ($status) => [$status['value'] => 0])->all();
        $byCategory = collect(Complaint::CATEGORIES)->mapWithKeys(fn ($category) => [$category => 0])->all();

        foreach ($complaints as $complaint) {
            $byStatus[$complaint->status]++;
            $byCategory[$complaint->category]++;
        }

        return ['total' => $complaints->count(), 'unassigned' => $complaints->whereNull('assigned_user_id')->count(), 'by_status' => $byStatus, 'by_category' => $byCategory];
    }
}
