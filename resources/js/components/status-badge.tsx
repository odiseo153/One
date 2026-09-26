import { Badge } from '@/components/ui/badge';

export function StatusBadge({ deletedAt }: { deletedAt: string | null }) {
    if (deletedAt) {
        return (
            <Badge variant="secondary" className="text-muted-foreground">
                Inactivo
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
        >
            Activo
        </Badge>
    );
}
