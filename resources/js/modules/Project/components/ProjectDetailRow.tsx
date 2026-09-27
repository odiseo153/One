import type { ReactNode } from 'react';

export function ProjectDetailRow({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="grid grid-cols-[130px_1fr] items-baseline gap-2 border-b border-dashed last:border-0">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="pb-2 text-sm font-medium break-words">{value}</dd>
        </div>
    );
}
