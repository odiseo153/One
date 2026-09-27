import type { ProjectReportChart } from '@/modules/Project/types/projectReport';

const COLORS = [
    '#2563eb',
    '#0891b2',
    '#16a34a',
    '#f59e0b',
    '#8b5cf6',
    '#ef4444',
];

type Props = { chart: Extract<ProjectReportChart, { kind: 'donut' }> };

export function DonutChart({ chart }: Props) {
    const total = chart.values.reduce((sum, value) => sum + value, 0);
    const values = chart.labels
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
    const segments = values.map((item, index) => {
        const start = accumulated;
        accumulated += item.value;
        return {
            ...item,
            color: COLORS[index % COLORS.length],
            from: (start / total) * 100,
            to: (accumulated / total) * 100,
        };
    });
    const gradient = segments
        .map((segment) => `${segment.color} ${segment.from}% ${segment.to}%`)
        .join(', ');

    return (
        <div className="flex flex-wrap items-center gap-6">
            <div
                className="relative size-40 shrink-0 rounded-full"
                style={{ background: `conic-gradient(${gradient})` }}
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
