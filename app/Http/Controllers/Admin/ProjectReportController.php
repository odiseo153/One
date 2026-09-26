<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Modules\Project\Domain\Services\ProjectReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportController extends Controller
{
    public function __construct(
        private readonly ProjectReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        $request->validate([
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'stagnant_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $filters = $this->filters($request);
        $municipalityId = $this->municipalityId($request);
        $report = $this->reportService->report($municipalityId, $filters);

        return Inertia::render('admin/projects/reports', [
            'report' => $report,
            'municipalities' => $this->municipalityOptions($request),
            'selectedMunicipality' => $this->selectedMunicipality($municipalityId),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse|BinaryFileResponse
    {
        $request->validate([
            'metric' => ['required', 'string'],
            'format' => ['required', Rule::in(['csv', 'pdf'])],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'stagnant_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $filters = $this->filters($request);
        $municipalityId = $this->municipalityId($request);
        $report = $this->reportService->report($municipalityId, $filters);

        $block = collect($report['blocks'])->firstWhere('slug', $request->input('metric'));

        abort_unless($block, 404, __('Métrica no encontrada.'));

        $meta = $this->meta($request, $filters);

        return $request->input('format') === 'csv'
            ? $this->downloadCsv($meta, $block)
            : $this->downloadPdf($meta, $block);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $block
     */
    private function downloadCsv(array $meta, array $block): StreamedResponse
    {
        $slug = $block['slug'];

        return response()->streamDownload(function () use ($meta, $block) {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [$block['title']], ';');
            fputcsv($output, [$meta['subtitle']], ';');
            fputcsv($output, [], ';');

            foreach ($block['summary'] as $row) {
                fputcsv($output, [$row['label'], $row['value']], ';');
            }

            fputcsv($output, [], ';');
            fputcsv($output, $block['headers'], ';');

            foreach ($block['rows'] as $row) {
                fputcsv($output, $row, ';');
            }

            fclose($output);
        }, 'reporte-'.$slug.'-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $block
     */
    private function downloadPdf(array $meta, array $block): BinaryFileResponse
    {
        $pdf = Pdf::loadView('exports.project-report', [
            'meta' => $meta,
            'block' => $block,
        ]);

        return $pdf->download('reporte-'.$block['slug'].'-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function meta(Request $request, array $filters): array
    {
        $municipality = $this->selectedMunicipality($this->municipalityId($request));

        return [
            'municipality' => $municipality['name'] ?? 'Todos los municipios',
            'generated_at' => now()->format('d/m/Y H:i'),
            'period' => $this->periodLabel($filters),
            'stagnant_days' => $filters['stagnant_days'],
            'subtitle' => $this->periodLabel($filters).($municipality['name'] ?? null ? ' · '.$municipality['name'] : ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function periodLabel(array $filters): string
    {
        if (! $filters['from'] && ! $filters['to']) {
            return 'Todo el período';
        }

        $from = $filters['from'] ? Carbon::parse($filters['from'])->format('d/m/Y') : 'inicio';
        $to = $filters['to'] ? Carbon::parse($filters['to'])->format('d/m/Y') : 'hoy';

        return "Período: {$from} – {$to}";
    }

    /**
     * @return array{from: ?string, to: ?string, stagnant_days: int}
     */
    private function filters(Request $request): array
    {
        return [
            'from' => $request->input('from') ?: null,
            'to' => $request->input('to') ?: null,
            'stagnant_days' => (int) ($request->input('stagnant_days') ?? ProjectReportService::DEFAULT_STAGNANT_DAYS),
        ];
    }

    private function municipalityId(Request $request): ?int
    {
        $userMunicipalityId = $request->user()?->municipality_id;

        if ($userMunicipalityId) {
            return (int) $userMunicipalityId;
        }

        $filtered = $request->integer('municipality_id');

        return $filtered > 0 ? $filtered : null;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function municipalityOptions(Request $request): array
    {
        if ($request->user()?->municipality_id) {
            return Municipality::query()
                ->where('id', $request->user()->municipality_id)
                ->get(['id', 'name'])
                ->toArray();
        }

        return Municipality::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function selectedMunicipality(?int $municipalityId): ?array
    {
        if (! $municipalityId) {
            return null;
        }

        return Municipality::query()->find($municipalityId, ['id', 'name'])?->toArray();
    }
}
