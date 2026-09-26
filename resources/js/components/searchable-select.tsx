import { Check, ChevronDown, Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { cn } from '@/lib/utils';

export type SearchableSelectOption = {
    value: string;
    label: string;
};

type SearchableSelectProps = {
    value: string;
    onValueChange: (value: string) => void;
    options: SearchableSelectOption[];
    placeholder?: string;
    emptyText?: string;
    disabled?: boolean;
    className?: string;
    id?: string;
};

export function SearchableSelect({
    value,
    onValueChange,
    options,
    placeholder = 'Select an option',
    emptyText = 'No results found.',
    disabled = false,
    className,
    id,
}: SearchableSelectProps) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [activeIndex, setActiveIndex] = useState(0);
    const rootRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const filtered = useMemo(() => {
        const term = query.trim().toLowerCase();
        if (!term) {
            return options;
        }
        return options.filter((option) =>
            option.label.toLowerCase().includes(term),
        );
    }, [options, query]);

    const selected = options.find((option) => option.value === value);

    useEffect(() => {
        if (open) {
            setQuery('');
            setActiveIndex(0);
            requestAnimationFrame(() => inputRef.current?.focus());
        }
    }, [open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handlePointerDown = (event: MouseEvent) => {
            if (
                rootRef.current &&
                !rootRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handlePointerDown);

        return () =>
            document.removeEventListener('mousedown', handlePointerDown);
    }, [open]);

    const selectOption = (optionValue: string) => {
        onValueChange(optionValue);
        setOpen(false);
    };

    const handleKeyDown = (event: React.KeyboardEvent) => {
        if (filtered.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) =>
                index >= filtered.length - 1 ? 0 : index + 1,
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) =>
                index <= 0 ? filtered.length - 1 : index - 1,
            );
        } else if (event.key === 'Enter') {
            event.preventDefault();
            selectOption(filtered[activeIndex].value);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
    };

    return (
        <div ref={rootRef} className={cn('relative', className)}>
            <button
                type="button"
                id={id}
                disabled={disabled}
                onClick={() => setOpen((isOpen) => !isOpen)}
                className={cn(
                    'border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*="text-"])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                    !selected && 'text-muted-foreground',
                )}
                aria-haspopup="listbox"
                aria-expanded={open}
            >
                <span className="truncate">
                    {selected?.label ?? placeholder}
                </span>
                <ChevronDown
                    className={cn(
                        'size-4 shrink-0 opacity-50 transition-transform',
                        open && 'rotate-180',
                    )}
                />
            </button>

            {open && (
                <div className="bg-popover text-popover-foreground animate-in fade-in-0 zoom-in-95 absolute top-full right-0 left-0 z-50 mt-2 origin-top rounded-md border shadow-md">
                    <div className="p-1">
                        <div className="flex items-center gap-2 border-b px-2 pb-2">
                            <Search className="text-muted-foreground size-4 shrink-0" />
                            <input
                                ref={inputRef}
                                type="text"
                                value={query}
                                onChange={(event) => {
                                    setQuery(event.target.value);
                                    setActiveIndex(0);
                                }}
                                onKeyDown={handleKeyDown}
                                placeholder="Search..."
                                className="placeholder:text-muted-foreground flex-1 bg-transparent text-sm outline-none"
                            />
                        </div>
                    </div>

                    <div
                        role="listbox"
                        className="max-h-60 overflow-y-auto p-1"
                    >
                        {filtered.length === 0 && (
                            <p className="text-muted-foreground px-2 py-6 text-center text-sm">
                                {emptyText}
                            </p>
                        )}
                        {filtered.map((option, index) => {
                            const isActive = index === activeIndex;
                            const isSelected = option.value === value;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    role="option"
                                    aria-selected={isSelected}
                                    onMouseDown={(event) => {
                                        event.preventDefault();
                                        selectOption(option.value);
                                    }}
                                    onMouseEnter={() => setActiveIndex(index)}
                                    className={cn(
                                        'flex w-full cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-left text-sm',
                                        isActive &&
                                            'bg-accent text-accent-foreground',
                                    )}
                                >
                                    <span className="truncate">
                                        {option.label}
                                    </span>
                                    {isSelected && (
                                        <Check className="size-4 shrink-0" />
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
