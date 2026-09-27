import { ArrowDown, ArrowUp } from 'lucide-react';

type Props = {
    label: string;
    column: string;
    sort?: string;
    onSort: (column: string) => void;
};

export function SortableTableHeader({ label, column, sort, onSort }: Props) {
    const active = sort === column || sort === `-${column}`;
    const descending = sort === `-${column}`;

    return (
        <th className="px-4 py-3 font-medium">
            <button
                type="button"
                className="hover:text-foreground inline-flex items-center gap-1"
                onClick={() => onSort(column)}
            >
                {label}
                {active ? (
                    descending ? (
                        <ArrowDown className="size-3.5" />
                    ) : (
                        <ArrowUp className="size-3.5" />
                    )
                ) : null}
            </button>
        </th>
    );
}
