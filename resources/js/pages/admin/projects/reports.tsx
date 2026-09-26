import { Head, router } from '@inertiajs/react';
import {
    BarChart3,
    CheckCircle2,
    Clock4,
    FileSpreadsheet,
    FileText,
    HardHat,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatMoney } from '@/lib/projects';

type Block = {
    slug: string;
    title: string;
    subtitle: string;
    summary: { label: string; value: string }[];
    headers: string[];
    rows: string[][];
    chart?: ChartData;
};

type ChartData =
    | {
          kind: 'grouped-bars';
          categories: string[];
          series: { name: string; values: number[] }[];
      }
    | { kind: 'bars'; labels: string[]; values: number[] }
    | { kind: 'donut'; labels: string[]; values: number[] };

type Props = {
    report: {
        municipality: number | null;
        filters: { from?: string | null; to?: string | null; stagnant_days: number };
        overall: {
            total: number;
            by_status: Record<string, number>;
            assigned: number;
            executed: number;
            execution_rate: number;
            stagnant: number;
        };
        blocks: Block[];
    };
    municipalities: { id: number; name: string }[];
    selectedMunicipality: { id: number; name: string } | null;
    filters: { from?: string | null; to?: string | null; stagnant_days: number };
};

const DONUT_COLORS = [
    '#2563eb',
    '#0891b2',
    '#16a34a',
    '#f59e0b',
    '#8b5cf6',
    '#ef4444',
];

const BAR_COLORS = ['#2563eb', '#f59e0b', '#16a34a', '#8b5cf6'];

export default function ProjectsReports({
    report,
    municipalities,
    selectedMunicipality,
    filters,
}: Props) {
    const overall = report.overall;
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [stagnantDays, setStagnantDays] = useState(String(filters.stagnant_days));
    const [municipalityId, setMunicipalityId] = useState(
        selectedMunicipality ? String(selectedMunicipality.id) : 'all',
    );

    const applyFilters = () => {
        router.get(
            '/admin/projects/reports',
            {
                ...(from ? { from } : {}),
                ...(to ? { to } : {}),
                stagnant_days: stagnantDays || undefined,
                ...(municipalityId !== 'all'
                    ? { municipality_id: municipalityId }
                    : {}),
            },
            { preserveScroll: true },
        );
    };

    const exportParams = `from=${from || ''}&to=${to || ''}&stagnant_days=${stagnantDays || 30}${municipalityId !== 'all' ? `&municipality_id=${municipalityId}` : ''}`;

    const summaryCards = [
        { label: 'Obras', value: overall.total, icon: HardHat },
        {
            label: 'En ejecución',
            value: overall.by_status.in_progress ?? 0,
            icon: TrendingUp,
        },
        {
            label: 'Completadas',
            value: overall.by_status.completed ?? 0,
            icon: CheckCircle2,
        },
        {
            label: 'Estancadas',
            value: overall.stagnant,
            icon: Clock4,
        },
        {
            label: 'Presupuesto ejecutado',
            value: formatMoney(overall.executed),
            icon: BarChart3,
        },
    ];

    return (
        <>
            <Head title="Reportes de obras" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Reportes de productividad"
                        description="Métricas de ejecución de obras para la rendición de cuentas del municipio."
                    />
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-3 md:grid-cols-5">
                            <div className="space-y-1">
                                <Label className="text-xs">Desde</Label>
                                <Input
                                    type="date"
                                    value={from}
                                    onChange={(e) => setFrom(e.target.value)}
                                />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs">Hasta</Label>
                                <Input
                                    type="date"
                                    value={to}
                                    onChange={(e) => setTo(e.target.value)}
                                />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs">
                                    Alerta de estancamiento (días)
                                </Label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={365}
                                    value={stagnantDays}
                                    onChange={(e) =>
                                        setStagnantDays(e.target.value)
                                    }
                                />
                            </div>
                            {municipalities.length > 1 && (
                                <div className="space-y-1">
                                    <Label className="text-xs">Municipio</Label>
                                    <Select
                                        value={municipalityId}
                                        onValueChange={setMunicipalityId}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                Todos los municipios
                                            </SelectItem>
                                            {municipalities.map((item) => (
                                                <SelectItem
                                                    key={item.id}
                                                    value={String(item.id)}
                                                >
                                                    {item.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="flex items-end">
                                <Button
                                    className="w-full"
                                    onClick={applyFilters}
                                >
                                    Aplicar filtros
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {summaryCards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-muted-foreground flex items-center gap-2 text-sm font-normal">
                                    <card.icon className="size-4" />
                                    {card.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold">
                                    {card.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="space-y-6">
                    {report.blocks.map((block) => (
                        <MetricCard
                            key={block.slug}
                            block={block}
                            exportParams={exportParams}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

function MetricCard({
    block,
    exportParams,
}: {
    block: Block;
    exportParams: string;
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle>{block.title}</CardTitle>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {block.subtitle}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={`/admin/projects/reports/export?metric=${block.slug}&format=csv&${exportParams}`}
                            >
                                <FileSpreadsheet />
                                Excel
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={`/admin/projects/reports/export?metric=${block.slug}&format=pdf&${exportParams}`}
                            >
                                <FileText />
                                PDF
                            </a>
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-5">
                <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
                    {block.summary.map((item) => (
                        <div
                            key={item.label}
                            className="rounded-lg border px-4 py-3"
                        >
                            <p className="text-muted-foreground text-xs font-medium">
                                {item.label}
                            </p>
                            <p className="text-xl font-semibold">{item.value}</p>
                        </div>
                    ))}
                </div>

                {block.chart?.kind === 'grouped-bars' && (
                    <GroupedBarsChart chart={block.chart} />
                )}
                {block.chart?.kind === 'bars' && (
                    <BarsChart chart={block.chart} />
                )}
                {block.chart?.kind === 'donut' && <DonutChart chart={block.chart} />}

                {block.rows.length > 0 && (
                    <div className="border-sidebar-border/70 max-h-80 overflow-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-muted-foreground sticky top-0 text-left">
                                <tr>
                                    {block.headers.map((header) => (
                                        <th
                                            key={header}
                                            className="px-3 py-2 font-medium"
                                        >
                                            {header}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-sidebar-border/50 divide-y">
                                {block.rows.map((row, index) => (
                                    <tr key={index} className="hover:bg-muted/30">
                                        {row.map((cell, cellIndex) => (
                                            <td
                                                key={cellIndex}
                                                className="px-3 py-2"
                                            >
                                                {cell}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {block.rows.length === 0 && block.slug === 'stagnant' && (
                    <Badge className="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                        No hay obras estancadas.
                    </Badge>
                )}
            </CardContent>
        </Card>
    );
}

function GroupedBarsChart({
    chart,
}: {
    chart: Extract<ChartData, { kind: 'grouped-bars' }>;
}) {
    const max = Math.max(
        1,
        ...chart.series.flatMap((series) => series.values),
    );

    return (
        <div className="space-y-3">
            <div className="flex gap-4 text-xs text-muted-foreground">
                {chart.series.map((series, index) => (
                    <span
                        key={series.name}
                        className="inline-flex items-center gap-1.5"
                    >
                        <span
                            className="inline-block size-2.5 rounded-sm"
                            style={{
                                background: BAR_COLORS[index % BAR_COLORS.length],
                            }}
                        />
                        {series.name}
                    </span>
                ))}
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {chart.categories.map((category, index) => (
                    <div key={category} className="space-y-1">
                        <p className="text-muted-foreground truncate text-xs">
                            {category}
                        </p>
                        <div className="flex items-end gap-1.5">
                            {chart.series.map((series, seriesIndex) => {
                                const value = series.values[index] ?? 0;

                                return (
                                    <div
                                        key={series.name}
                                        className="flex flex-1 flex-col items-center gap-1"
                                    >
                                        <div className="flex h-24 w-full items-end">
                                            <div
                                                className="w-full rounded-t"
                                                style={{
                                                    height: `${(value / max) * 100}%`,
                                                    background:
                                                        BAR_COLORS[
                                                            seriesIndex %
                                                                BAR_COLORS.length
                                                        ],
                                                }}
                                            />
                                        </div>
                                        <span className="text-muted-foreground text-xs tabular-nums">
                                            {value}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function BarsChart({
    chart,
}: {
    chart: Extract<ChartData, { kind: 'bars' }>;
}) {
    const max = Math.max(1, ...chart.values);

    return (
        <div className="space-y-2">
            {chart.labels.map((label, index) => (
                <div key={label} className="space-y-1">
                    <div className="flex items-center justify-between text-xs">
                        <span className="text-muted-foreground truncate">
                            {label}
                        </span>
                        <span className="font-medium tabular-nums">
                            {chart.values[index]}
                        </span>
                    </div>
                    <div className="bg-muted h-2.5 w-full overflow-hidden rounded-full">
                        <div
                            className="bg-primary h-full rounded-full"
                            style={{
                                width: `${(chart.values[index] / max) * 100}%`,
                            }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}

function DonutChart({
    chart,
}: {
    chart: Extract<ChartData, { kind: 'donut' }>;
}) {
    const total = chart.values.reduce((sum, value) => sum + value, 0);
    const withValues = chart.labels
        .map((label, index) => ({ label, value: chart.values[index] ?? 0 }))
        .filter((item) => item.value > 0);

    if (total === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                Sin datos para el período.
            </p>
        );
    }

    let accumulated = 0;
    const segments = withValues.map((item, index) => {
        const start = accumulated;
        accumulated += item.value;
        const color = DONUT_COLORS[index % DONUT_COLORS.length];

        return {
            ...item,
            color,
            from: (start / total) * 100,
            to: (accumulated / total) * 100,
        };
    });

    const gradient = segments
        .map(
            (segment) =>
                `${segment.color} ${segment.from}% ${segment.to}%`,
        )
        .join(', ');

    return (
        <div className="flex flex-wrap items-center gap-6">
            <div
                className="relative size-40 shrink-0 rounded-full"
                style={{
                    background: `conic-gradient(${gradient})`,
                }}
            >
                <div className="bg-background absolute inset-5 flex items-center justify-center rounded-full">
                    <div className="text-center">
                        <p className="text-2xl font-semibold">{total}</p>
                        <p className="text-muted-foreground text-xs">obras</p>
                    </div>
                </div>
            </div>
            <div className="space-y-2">
                {segments.map((segment) => (
                    <div
                        key={segment.label}
                        className="flex items-center gap-2 text-sm"
                    >
                        <span
                            className="inline-block size-3 rounded-sm"
                            style={{ background: segment.color }}
                        />
                        <span className="text-muted-foreground">
                            {segment.label}
                        </span>
                        <span className="font-medium">{segment.value}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
