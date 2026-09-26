<?php

namespace App\Modules\Complaint\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Complaint as ComplaintModel;
use App\Modules\Complaint\Domain\Contracts\ComplaintRepositoryPort;
use App\Modules\Complaint\Domain\Entities\Complaint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

class ComplaintRepository extends BaseRepository implements ComplaintRepositoryPort
{
    public function __construct()
    {
        parent::__construct(new ComplaintModel);
    }

    /**
     * Setup Complaint-specific filters, sorts and includes
     * Customize this method to define what can be filtered, sorted, and included
     */
    protected function setupDefaults()
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
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
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
            AllowedFilter::exact('resolved_at'),
        ];

        $this->allowedSorts = [
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

        $this->allowedIncludes = [
            AllowedInclude::relationship('municipality'),
            AllowedInclude::relationship('sector'),
            AllowedInclude::relationship('assignedUser'),
            AllowedInclude::relationship('updates'),
        ];

        $this->defaultSort = '-created_at';
    }

    public function getAll(int $perPage, ?string $defaultSort = null, array $with = []): LengthAwarePaginator
    {
        // Spatie Query Builder will automatically handle:
        // - Filtering: GET /complaints?filter[status]=received&filter[municipality_id]=1
        // - Sorting: GET /complaints?sort=-created_at,tracking_code
        // - Including: GET /complaints?include=municipality,sector,updates

        return parent::getAll($perPage, $defaultSort, $with);
    }

    public function create(array $data)
    {
        $data['tracking_code'] ??= ComplaintModel::newTrackingCode();
        $data['status'] ??= ComplaintModel::STATUS_RECEIVED;

        $complaint = ComplaintModel::create($data);

        $complaint->load($this->includeNames());

        return new Complaint($complaint->toArray());
    }

    public function findById($id)
    {
        $complaint = ComplaintModel::with($this->includeNames())->findOrFail($id);

        return new Complaint($complaint->toArray());
    }
}
