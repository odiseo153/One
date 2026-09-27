import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ProjectForm } from '@/modules/Project/components/ProjectForm';
import { ProjectBackButton } from '@/modules/Project/components/ProjectBackButton';
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
                    <ProjectBackButton />
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
