import { FileSpreadsheet, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BarsChart } from '@/modules/Project/components/BarsChart';
import { DonutChart } from '@/modules/Project/components/DonutChart';
import { GroupedBarsChart } from '@/modules/Project/components/GroupedBarsChart';
import type { ProjectReportBlock } from '@/modules/Project/types/projectReport';

type Props = { block: ProjectReportBlock; exportParams: string };

export function ProjectReportMetricCard({ block, exportParams }: Props) {
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
                                <FileSpreadsheet /> Excel
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={`/admin/projects/reports/export?metric=${block.slug}&format=pdf&${exportParams}`}
                            >
                                <FileText /> PDF
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
                            <p className="text-xl font-semibold">
                                {item.value}
                            </p>
                        </div>
                    ))}
                </div>
                {block.chart?.kind === 'grouped-bars' ? (
                    <GroupedBarsChart chart={block.chart} />
                ) : null}
                {block.chart?.kind === 'bars' ? (
                    <BarsChart chart={block.chart} />
                ) : null}
                {block.chart?.kind === 'donut' ? (
                    <DonutChart chart={block.chart} />
                ) : null}
                {block.rows.length > 0 ? (
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
                                    <tr
                                        key={index}
                                        className="hover:bg-muted/30"
                                    >
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
                ) : null}
                {block.rows.length === 0 && block.slug === 'stagnant' ? (
                    <Badge className="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                        No hay obras estancadas.
                    </Badge>
                ) : null}
            </CardContent>
        </Card>
    );
}
