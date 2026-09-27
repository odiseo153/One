import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import {
    type ProjectFormProject,
    ProjectForm,
} from '@/modules/Project/components/ProjectForm';
import { Card, CardContent } from '@/components/ui/card';

type MunicipalityOption = { id: number; name: string };
type SectorOption = { id: number; municipality_id: number; name: string };

type Props = {
    project: ProjectFormProject;
    municipalities: MunicipalityOption[];
    defaultMunicipality: MunicipalityOption | null;
    sectors: SectorOption[];
};

export default function EditProject({
    project,
    municipalities,
    defaultMunicipality,
    sectors,
}: Props) {
    return (
        <>
            <Head title="Editar obra" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <Link
                        href={`/admin/projects/${project.id}`}
                        className="text-muted-foreground hover:text-foreground mb-2 inline-flex items-center gap-1 text-sm"
                    >
                        <ArrowLeft className="size-4" />
                        Volver a la obra
                    </Link>
                    <Heading
                        title="Editar obra"
                        description="Actualiza los datos generales de la obra."
                    />
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <ProjectForm
                            editing
                            project={project}
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
