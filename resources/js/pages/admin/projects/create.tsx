import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { ProjectForm } from '@/components/project-form';
import { Card, CardContent } from '@/components/ui/card';

type MunicipalityOption = { id: number; name: string };
type SectorOption = { id: number; municipality_id: number; name: string };

type Props = {
    municipalities: MunicipalityOption[];
    defaultMunicipality: MunicipalityOption | null;
    sectors: SectorOption[];
};

export default function CreateProject({
    municipalities,
    defaultMunicipality,
    sectors,
}: Props) {
    return (
        <>
            <Head title="Nueva obra" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <ButtonBack />
                    <Heading
                        title="Nueva obra"
                        description="Registra una obra o proyecto de infraestructura del municipio."
                    />
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <ProjectForm
                            municipalities={municipalities}
                            fixedMunicipality={defaultMunicipality}
                            sectors={sectors}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ButtonBack() {
    return (
        <Link
            href="/admin/projects"
            className="text-muted-foreground hover:text-foreground mb-2 inline-flex items-center gap-1 text-sm"
        >
            <ArrowLeft className="size-4" />
            Volver a obras
        </Link>
    );
}
