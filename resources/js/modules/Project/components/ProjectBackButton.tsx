import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export function ProjectBackButton() {
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
