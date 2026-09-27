<?php

namespace App\Modules\Complaint\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntityDateHelper;
use App\Core\Support\EntitySearchHelper;
use App\Models\Complaint as ComplaintModel;
use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User;
use App\Modules\Complaint\Domain\Contracts\ComplaintRepositoryPort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<ComplaintModel> */
class ComplaintRepository extends BaseRepository implements ComplaintRepositoryPort
{
    public function __construct()
    {
        parent::__construct(ComplaintModel::class);
    }

    protected function getFilters(): array
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::callback('search', fn ($query, $value) => EntitySearchHelper::apply(
                $query,
                $value,
                ['columns' => ['description', 'address_text', 'citizen_name', 'citizen_phone', 'tracking_code']],
            )),
            AllowedFilter::exact('tracking_code'),
            AllowedFilter::partial('description'),
            AllowedFilter::partial('address_text'),
            AllowedFilter::partial('citizen_name'),
            AllowedFilter::partial('citizen_phone'),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::exact('sector_id'),
            AllowedFilter::exact('category'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('assigned_user_id'),
            AllowedFilter::callback('assigned', function ($query, mixed $value): void {
                $assigned = Arr::wrap($value);
                $query->where(function ($query) use ($assigned): void {
                    in_array('unassigned', $assigned, true) && $query->orWhereNull('assigned_user_id');
                    in_array('assigned', $assigned, true) && $query->orWhereNotNull('assigned_user_id');
                });
            }),
            AllowedFilter::callback('from', fn ($query, $value) => EntityDateHelper::applyFrom($query, $value)),
            AllowedFilter::callback('to', fn ($query, $value) => EntityDateHelper::applyTo($query, $value)),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
            AllowedFilter::exact('resolved_at'),
        ];
    }

    protected function getSorts(): array
    {
        return [
            AllowedSort::field('id'),
            AllowedSort::field('tracking_code'),
            AllowedSort::field('category'),
            AllowedSort::field('status'),
            AllowedSort::field('municipality_id'),
            AllowedSort::field('sector_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
            AllowedSort::field('resolved_at'),
        ];
    }

    protected function getWith(): array
    {
        return ['municipality', 'sector', 'assignedUser', 'updates'];
    }

    public function create(array $data): ComplaintModel
    {
        $data['tracking_code'] ??= ComplaintModel::newTrackingCode();
        $data['status'] ??= ComplaintModel::STATUS_RECEIVED;

        $complaint = parent::create($data);

        return $complaint;
    }

    public function getManagementData(?int $municipalityId, array $filters): array
    {
        return [
            'complaints' => $this->getAll(
                15,
                '-id',
                ['sector:id,name', 'assignedUser:id,name'],
                [...$filters, 'municipality_id' => $municipalityId],
            ),
            'sectors' => Sector::query()
                ->whereNull('deleted_at')
                ->where('municipality_id', $municipalityId)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    public function findManaged(?int $municipalityId, int $id): ComplaintModel
    {
        return ComplaintModel::query()
            ->where('municipality_id', $municipalityId)
            ->with(['municipality:id,name', 'sector:id,name', 'assignedUser:id,name', 'updates.user:id,name'])
            ->withTrashed()
            ->findOrFail($id);
    }

    public function getUserOptions(?int $municipalityId): array
    {
        return User::query()
            ->whereNull('deleted_at')
            ->where('municipality_id', $municipalityId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function assign(?int $municipalityId, int $id, ?int $userId): void
    {
        $this->findManaged($municipalityId, $id)->assignTo($userId);
    }

    public function changeStatus(?int $municipalityId, int $id, string $status, ?int $userId, ?string $note): void
    {
        $this->findManaged($municipalityId, $id)->changeStatus($status, $userId, $note);
    }

    /**
     * @return EloquentCollection<int, ComplaintModel>
     */
    public function getStatsRows(?int $municipalityId): EloquentCollection
    {
        return ComplaintModel::query()
            ->where('municipality_id', $municipalityId)
            ->get(['status', 'category', 'assigned_user_id']);
    }

    /**
     * @return EloquentCollection<int, Municipality>
     */
    public function getActiveMunicipalities(): EloquentCollection
    {
        return Municipality::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return EloquentCollection<int, Sector>
     */
    public function getPublicSectorOptions(): EloquentCollection
    {
        return Sector::query()
            ->whereHas('municipality', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name']);
    }

    /**
     * @return EloquentCollection<int, Sector>
     */
    public function getSectorsWithGeometry(int $municipalityId): EloquentCollection
    {
        return Sector::query()
            ->where('municipality_id', $municipalityId)
            ->whereNotNull('geojson_polygon')
            ->get(['id', 'geojson_polygon']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPublic(array $data): ComplaintModel
    {
        $data['tracking_code'] ??= ComplaintModel::newTrackingCode();
        $data['status'] ??= ComplaintModel::STATUS_RECEIVED;

        return ComplaintModel::create($data);
    }

    public function findPublicByTrackingCode(string $trackingCode): ComplaintModel
    {
        return ComplaintModel::query()
            ->where('tracking_code', $trackingCode)
            ->with([
                'municipality:id,name',
                'sector:id,name',
                'updates' => fn ($query) => $query->with('user:id,name'),
            ])
            ->firstOrFail();
    }
}
