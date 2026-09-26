import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

function cleanLabel(label: string): string {
    return label
        .replace(/&laquo;|&raquo;|&nbsp;/g, ' ')
        .replace(/Previous|Next|Anterior|Siguiente/g, '')
        .trim();
}

export function Pagination({
    links,
    from,
    to,
    total,
}: {
    links: PaginationLink[];
    from?: number;
    to?: number;
    total?: number;
}) {
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <div className="border-sidebar-border/70 flex flex-wrap items-center justify-between gap-4 border-t pt-4">
            <p className="text-muted-foreground text-sm">
                {from != null && to != null && total != null
                    ? `Mostrando ${from}–${to} de ${total}`
                    : ''}
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {links.map((link, index) => {
                    const isFirst = index === 0;
                    const isLast = index === links.length - 1;
                    const label = isFirst
                        ? '‹'
                        : isLast
                          ? '›'
                          : cleanLabel(link.label);

                    if (!link.url) {
                        return (
                            <span
                                key={index}
                                className="text-muted-foreground inline-flex h-8 items-center rounded-md px-2 text-sm opacity-40"
                            >
                                {label}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'hover:bg-accent hover:text-accent-foreground inline-flex h-8 items-center rounded-md px-2.5 text-sm transition-colors',
                                link.active &&
                                    'bg-primary text-primary-foreground',
                            )}
                        >
                            {label}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
