import type { ProjectReportChart } from '@/modules/Project/types/projectReport';

type Props = { chart: Extract<ProjectReportChart, { kind: 'bars' }> };

export function BarsChart({ chart }: Props) {
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
