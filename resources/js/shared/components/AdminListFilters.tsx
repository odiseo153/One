import { Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    search: string;
    status: string;
    placeholder: string;
    onSearchChange: (value: string) => void;
    onSearch: () => void;
    onStatusChange: (value: string) => void;
};

export function AdminListFilters({
    search,
    status,
    placeholder,
    onSearchChange,
    onSearch,
    onStatusChange,
}: Props) {
    return (
        <div className="flex flex-wrap items-center gap-2">
            <div className="relative">
                <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                <Input
                    className="w-64 pl-8"
                    placeholder={placeholder}
                    value={search}
                    onChange={(event) => onSearchChange(event.target.value)}
                    onKeyDown={(event) => event.key === 'Enter' && onSearch()}
                />
            </div>
            <Button variant="outline" onClick={onSearch}>
                Buscar
            </Button>
            <Select value={status} onValueChange={onStatusChange}>
                <SelectTrigger className="w-[150px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="active">Activos</SelectItem>
                    <SelectItem value="inactive">Inactivos</SelectItem>
                    <SelectItem value="all">Todos</SelectItem>
                </SelectContent>
            </Select>
        </div>
    );
}
