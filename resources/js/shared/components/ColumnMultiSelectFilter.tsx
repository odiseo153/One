import { ChevronDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Option = { value: string; label: string };

type Props = {
    label: string;
    options: Option[];
    selected: string[];
    onToggle: (value: string) => void;
};

export function ColumnMultiSelectFilter({
    label,
    options,
    selected,
    onToggle,
}: Props) {
    const active = selected.length > 0;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={`group flex cursor-pointer items-center gap-1 font-medium outline-none select-none ${
                    active
                        ? 'text-foreground'
                        : 'hover:text-foreground text-muted-foreground'
                }`}
            >
                <span>{label}</span>
                <span
                    className={`flex items-center justify-center rounded-full text-xs tabular-nums ${
                        active
                            ? 'bg-primary text-primary-foreground size-5'
                            : 'size-4'
                    }`}
                >
                    {active ? selected.length : null}
                </span>
                <ChevronDown
                    className={`size-3 transition-transform group-data-[state=open]:rotate-180 ${
                        active ? 'opacity-100' : 'opacity-50'
                    }`}
                />
            </DropdownMenuTrigger>
            <DropdownMenuContent className="max-h-72 overflow-y-auto">
                {options.map((option) => (
                    <DropdownMenuCheckboxItem
                        key={option.value}
                        checked={selected.includes(option.value)}
                        onCheckedChange={() => onToggle(option.value)}
                        onSelect={(event) => event.preventDefault()}
                    >
                        {option.label}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
