import { Head } from '@inertiajs/react';
import {
    BarChart3,
    CheckCircle2,
    Clock4,
    HardHat,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
import { ProjectReportMetricCard } from '@/modules/Project/components/ProjectReportMetricCard';
import { formatMoney } from '@/modules/Project/helpers/projectFormatting';
import { projectService } from '@/modules/Project/services/projectService';
import type { ProjectReportBlock } from '@/modules/Project/types/projectReport';

type Props = {
    report: {
        municipality: number | null;
        filters: {
            from?: string | null;
            to?: string | null;
            stagnant_days: number;
        };
        overall: {
            total: number;
            by_status: Record<string, number>;
            assigned: number;
            executed: number;
            execution_rate: number;
            stagnant: number;
        };
        blocks: ProjectReportBlock[];
    };
    municipalities: { id: number; name: string }[];
    selectedMunicipality: { id: number; name: string } | null;
    filters: {
        from?: string | null;
        to?: string | null;
        stagnant_days: number;
    };
};

export default function ProjectsReports({
    report,
    municipalities,
    selectedMunicipality,
    filters,
}: Props) {
    const overall = report.overall;
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [stagnantDays, setStagnantDays] = useState(
        String(filters.stagnant_days),
    );
    const [municipalityId, setMunicipalityId] = useState(
        selectedMunicipality ? String(selectedMunicipality.id) : 'all',
    );

    const applyFilters = () => {
        projectService.filterReports({
            ...(from ? { from } : {}),
            ...(to ? { to } : {}),
            stagnant_days: stagnantDays || undefined,
            ...(municipalityId !== 'all'
                ? { municipality_id: municipalityId }
                : {}),
        });
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
        { label: 'Estancadas', value: overall.stagnant, icon: Clock4 },
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
                <Heading
                    title="Reportes de productividad"
                    description="Métricas de ejecución de obras para la rendición de cuentas del municipio."
                />
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-3 md:grid-cols-5">
                            <div className="space-y-1">
                                <Label className="text-xs">Desde</Label>
                                <Input
                                    type="date"
                                    value={from}
                                    onChange={(event) =>
                                        setFrom(event.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs">Hasta</Label>
                                <Input
                                    type="date"
                                    value={to}
                                    onChange={(event) =>
                                        setTo(event.target.value)
                                    }
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
                                    onChange={(event) =>
                                        setStagnantDays(event.target.value)
                                    }
                                />
                            </div>
                            {municipalities.length > 1 ? (
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
                            ) : null}
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
                        <ProjectReportMetricCard
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
