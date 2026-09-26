<?php

namespace App\Modules\Complaint\Adapters\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    private const TRANSITIONS = [
        Complaint::STATUS_RECEIVED => [Complaint::STATUS_IN_PROGRESS, Complaint::STATUS_RESOLVED],
        Complaint::STATUS_IN_PROGRESS => [Complaint::STATUS_RECEIVED, Complaint::STATUS_RESOLVED],
        Complaint::STATUS_RESOLVED => [Complaint::STATUS_IN_PROGRESS],
    ];

    public function index(Request $request): Response
    {
        $municipalityId = $this->municipalityId($request);

        $query = Complaint::query()
            ->where('municipality_id', $municipalityId)
            ->with([
                'sector:id,name',
                'assignedUser:id,name',
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('address_text', 'like', "%{$search}%")
                    ->orWhere('citizen_name', 'like', "%{$search}%")
                    ->orWhere('citizen_phone', 'like', "%{$search}%")
                    ->orWhere('tracking_code', 'like', "%{$search}%");
            });
        }

        if ($status = array_filter((array) $request->input('status', []))) {
            $query->whereIn('status', $status);
        }

        if ($category = array_filter((array) $request->input('category', []))) {
            $query->whereIn('category', $category);
        }

        if ($sectorId = array_filter((array) $request->input('sector_id', []))) {
            $query->whereIn('sector_id', $sectorId);
        }

        if ($assigned = array_filter((array) $request->input('assigned', []))) {
            $query->where(function ($q) use ($assigned) {
                if (in_array('unassigned', $assigned, true)) {
                    $q->orWhereNull('assigned_user_id');
                }
                if (in_array('assigned', $assigned, true)) {
                    $q->orWhereNotNull('assigned_user_id');
                }
            });
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $complaints = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/complaints/index', [
            'complaints' => $complaints,
            'sectors' => Sector::query()
                ->whereNull('deleted_at')
                ->where('municipality_id', $municipalityId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => $this->categories(),
            'statuses' => $this->statuses(),
            'filters' => $request->only([
                'search',
                'status',
                'category',
                'sector_id',
                'assigned',
                'from',
                'to',
            ]),
            'stats' => $this->stats($municipalityId),
        ]);
    }

    public function show(Request $request, int $complaint): Response
    {
        $complaint = $this->scopedComplaint($request, $complaint)
            ->load([
                'municipality:id,name',
                'sector:id,name',
                'assignedUser:id,name',
                'updates' => fn ($query) => $query->with('user:id,name'),
            ]);

        return Inertia::render('admin/complaints/show', [
            'complaint' => $complaint,
            'users' => User::query()
                ->whereNull('deleted_at')
                ->where('municipality_id', $this->municipalityId($request))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function assign(Request $request, int $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('municipality_id', $this->municipalityId($request)),
            ],
        ]);

        $complaint = $this->scopedComplaint($request, $complaint);
        $complaint->assignTo($validated['assigned_user_id'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Queja actualizada.'),
        ]);

        return back();
    }

    public function updateStatus(Request $request, int $complaint): RedirectResponse
    {
        $complaint = $this->scopedComplaint($request, $complaint);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(self::TRANSITIONS[$complaint->status] ?? []),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $complaint->changeStatus(
            $validated['status'],
            $request->user()?->id,
            $validated['note'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Estado actualizado.'),
        ]);

        return back();
    }

    private function scopedComplaint(Request $request, int $id): Complaint
    {
        return Complaint::query()
            ->where('municipality_id', $this->municipalityId($request))
            ->with([
                'sector:id,name',
                'assignedUser:id,name',
                'updates' => fn ($query) => $query->with('user:id,name'),
            ])
            ->withTrashed()
            ->findOrFail($id);
    }

    private function municipalityId(Request $request): ?int
    {
        return $request->user()?->municipality_id;
    }

    /**
     * @return array{by_status: array<string, int>, by_category: array<string, int>, total: int, unassigned: int}
     */
    private function stats(?int $municipalityId): array
    {
        $query = Complaint::query()->where('municipality_id', $municipalityId);

        $byStatus = [
            Complaint::STATUS_RECEIVED => 0,
            Complaint::STATUS_IN_PROGRESS => 0,
            Complaint::STATUS_RESOLVED => 0,
        ];

        $byCategory = collect(Complaint::CATEGORIES)
            ->mapWithKeys(fn (string $category) => [$category => 0])
            ->all();

        $query->select(['status', 'category'])
            ->get()
            ->each(function (Complaint $complaint) use (&$byStatus, &$byCategory) {
                if (isset($byStatus[$complaint->status])) {
                    $byStatus[$complaint->status]++;
                }
                if (isset($byCategory[$complaint->category])) {
                    $byCategory[$complaint->category]++;
                }
            });

        return [
            'total' => array_sum($byStatus),
            'unassigned' => Complaint::query()
                ->where('municipality_id', $municipalityId)
                ->whereNull('assigned_user_id')
                ->count(),
            'by_status' => $byStatus,
            'by_category' => $byCategory,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function categories(): array
    {
        return [
            ['value' => Complaint::CATEGORY_BACHE, 'label' => 'Bache'],
            ['value' => Complaint::CATEGORY_ALUMBRADO, 'label' => 'Alumbrado'],
            ['value' => Complaint::CATEGORY_BASURA, 'label' => 'Basura'],
            ['value' => Complaint::CATEGORY_AGUA, 'label' => 'Agua'],
            ['value' => Complaint::CATEGORY_OTRO, 'label' => 'Otro'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return [
            ['value' => Complaint::STATUS_RECEIVED, 'label' => 'Recibida'],
            ['value' => Complaint::STATUS_IN_PROGRESS, 'label' => 'En proceso'],
            ['value' => Complaint::STATUS_RESOLVED, 'label' => 'Resuelta'],
        ];
    }
}
