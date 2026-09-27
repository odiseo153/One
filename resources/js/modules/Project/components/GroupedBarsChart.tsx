import type { ProjectReportChart } from '@/modules/Project/types/projectReport';

const COLORS = ['#2563eb', '#f59e0b', '#16a34a', '#8b5cf6'];

type Props = {
    chart: Extract<ProjectReportChart, { kind: 'grouped-bars' }>;
};

export function GroupedBarsChart({ chart }: Props) {
    const max = Math.max(1, ...chart.series.flatMap((series) => series.values));

    return (
        <div className="space-y-3">
            <div className="text-muted-foreground flex gap-4 text-xs">
                {chart.series.map((series, index) => (
                    <span
                        key={series.name}
                        className="inline-flex items-center gap-1.5"
                    >
                        <span
                            className="inline-block size-2.5 rounded-sm"
                            style={{
                                background: COLORS[index % COLORS.length],
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
                                                        COLORS[
                                                            seriesIndex %
                                                                COLORS.length
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
