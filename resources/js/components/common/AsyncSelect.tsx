import axios from '@/lib/axios';
import { Check, ChevronDown, Loader2, Search } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useDebounce } from '@/hooks/useDebounce';
import { cn } from '@/lib/utils';

export interface AsyncSelectProps<T extends object = Record<string, unknown>> {
    value?: string;
    /** Handler for value changes. Optional when used in filter descriptors (injected by FilterModal). */
    onValueChange?: (value: string) => void;
    onItemSelect?: (item: T) => void;
    url: string;
    placeholder?: string;
    className?: string;
    labelFn?: (item: T) => string;
    valueFn?: (item: T) => string;
    initialLabel?: string;
    label?: string;
    /** Meta key to read from API response for auto-selecting a default value when no value is set. */
    preferredMetaKey?: string;
    filterItems?: (item: T) => boolean;
    disabled?: boolean;
    leadingOption?: { value: string; label: string };
}

export function AsyncSelect<T extends object = Record<string, unknown>>({
    value,
    onValueChange,
    onItemSelect,
    url,
    placeholder = 'Select...',
    className,
    labelFn = (item: Readonly<T>) => (item as unknown as { name: string }).name,
    valueFn = (item: T) =>
        (item as unknown as { id: number | string }).id.toString(),
    initialLabel,
    label,
    preferredMetaKey,
    filterItems,
    disabled = false,
    leadingOption,
}: Readonly<AsyncSelectProps<T>>) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');
    const [items, setItems] = React.useState<T[]>([]);
    const [loading, setLoading] = React.useState(false);
    const [selectedLabel, setSelectedLabel] = React.useState<string>(
        initialLabel || '',
    );
    const [initialLoadDone, setInitialLoadDone] = React.useState(false);
    const [preferredResolved, setPreferredResolved] = React.useState(false);

    React.useEffect(() => {
        if (!preferredMetaKey || preferredResolved || value) return;

        const resolvePreferred = async () => {
            try {
                const response = await axios.get(url, {
                    params: { per_page: 1 },
                });
                const preferredId = response.data?.meta?.[preferredMetaKey];
                if (preferredId) {
                    onValueChange?.(String(preferredId));
                }
            } catch {
                // Silently fail — user can still pick manually
            } finally {
                setPreferredResolved(true);
            }
        };

        void resolvePreferred();
    }, [preferredMetaKey, preferredResolved, value, url, onValueChange]);

    const debouncedSearch = useDebounce(search, 300);

    const selectItem = React.useCallback(
        (item: T) => {
            const itemValue = valueFn(item);
            const itemLabel = labelFn(item);

            onValueChange?.(itemValue);
            onItemSelect?.(item);
            setSelectedLabel(itemLabel);
            setOpen(false);
        },
        [labelFn, onItemSelect, onValueChange, valueFn],
    );

    const fetchItems = React.useCallback(
        async (query: string) => {
            setLoading(true);
            try {
                const response = await axios.get(url, {
                    params: { search: query, per_page: 50 },
                });
                // Handle Laravel Resource Collection structure: { data: [...] }
                const data = response.data.data
                    ? response.data.data
                    : response.data;
                const rows: T[] = Array.isArray(data) ? data : [];
                setItems(filterItems ? rows.filter(filterItems) : rows);
            } catch (error) {
                console.error('Failed to fetch items', error);
                setItems([]);
            } finally {
                setLoading(false);
            }
        },
        [url, filterItems],
    );

    // Initial fetch to get label if value is provided and initialLabel is not present
    React.useEffect(() => {
        const fetchInitialLabel = async () => {
            if (
                value &&
                value !== 'null' &&
                value !== 'undefined' &&
                value !== leadingOption?.value &&
                !selectedLabel &&
                !initialLoadDone &&
                !initialLabel
            ) {
                try {
                    // Try to fetch specific item by ID
                    const [baseUrl] = url.split('?');
                    const response = await axios.get(`${baseUrl}/${value}`);
                    const data = response.data.data || response.data;
                    if (data) {
                        setSelectedLabel(labelFn(data));
                    }
                } catch {
                    // If fetch by ID fails, fallback to list fetch?
                    // Or maybe the value is invalid.
                } finally {
                    setInitialLoadDone(true);
                }
            } else if (initialLabel && value && !initialLoadDone) {
                setSelectedLabel(initialLabel);
                setInitialLoadDone(true);
            }
        };

        void fetchInitialLabel();
    }, [
        value,
        url,
        selectedLabel,
        initialLoadDone,
        labelFn,
        initialLabel,
        leadingOption,
    ]);

    // Fetch items on open or search
    React.useEffect(() => {
        if (open) {
            void fetchItems(debouncedSearch);
        }
    }, [open, debouncedSearch, fetchItems]);

    // Update selected label from items if available (e.g. after search selection)
    React.useEffect(() => {
        if (value && items.length > 0) {
            const item = items.find((i) => valueFn(i) === value);
            if (item) {
                setSelectedLabel(labelFn(item));
            }
        }
    }, [value, items, valueFn, labelFn]);

    // Clear label if value is cleared or invalid
    React.useEffect(() => {
        if (!value || value === 'null' || value === 'undefined')
            setSelectedLabel('');
    }, [value]);

    React.useEffect(() => {
        if (leadingOption && value === leadingOption.value) {
            setSelectedLabel(leadingOption.label);
        }
    }, [value, leadingOption]);

    return (
        <Popover open={open && !disabled} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    disabled={disabled}
                    aria-label={label}
                    aria-expanded={open}
                    className={cn(
                        'h-8 w-full min-w-0 justify-between overflow-hidden rounded-[6px] border border-transparent bg-secondary shadow-none px-2.5 text-sm font-normal hover:bg-secondary focus-visible:ring-2 focus-visible:ring-[2px] focus-visible:ring-ring/40',
                        !value && 'text-muted-foreground',
                        className,
                    )}
                >
                    <span className="flex-1 truncate text-left">
                        {selectedLabel || placeholder}
                    </span>
                    <ChevronDown className="ml-2 size-3.5 shrink-0 text-muted-foreground" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-[--radix-popover-trigger-width] p-0"
                align="start"
            >
                <div className="flex flex-col">
                    <div className="flex items-center border-b px-3">
                        <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                        <Input
                            placeholder="Search..."
                            className="flex h-11 w-full rounded-md border-0 bg-transparent px-0 py-3 text-sm shadow-none outline-none placeholder:text-muted-foreground focus-visible:ring-0 disabled:cursor-not-allowed disabled:opacity-50"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <ScrollArea className="max-h-[200px]">
                        <ul className="p-1" aria-busy={loading}>
                            {loading && (
                                <li className="list-none p-4">
                                    <div className="flex items-center justify-center">
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    </div>
                                </li>
                            )}
                            {!loading && leadingOption && !search && (
                                <li className="list-none">
                                    <button
                                        type="button"
                                        aria-pressed={
                                            leadingOption.value === value
                                        }
                                        className={cn(
                                            'relative flex w-full cursor-pointer items-center rounded-sm px-2 py-1.5 text-left text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground',
                                            leadingOption.value === value
                                                ? 'bg-accent text-accent-foreground'
                                                : '',
                                        )}
                                        onClick={() => {
                                            onValueChange?.(
                                                leadingOption.value,
                                            );
                                            setSelectedLabel(
                                                leadingOption.label,
                                            );
                                            setOpen(false);
                                        }}
                                    >
                                        <Check
                                            className={cn(
                                                'mr-2 h-4 w-4',
                                                leadingOption.value === value
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        {leadingOption.label}
                                    </button>
                                </li>
                            )}
                            {!loading && items.length === 0 && (
                                <li className="list-none py-6 text-center text-sm text-muted-foreground">
                                    No results found.
                                </li>
                            )}
                            {!loading &&
                                items.map((item) => {
                                    const itemValue = valueFn(item);
                                    const itemLabel = labelFn(item);
                                    return (
                                        <li
                                            key={itemValue}
                                            className="list-none"
                                        >
                                            <button
                                                type="button"
                                                aria-pressed={
                                                    itemValue === value
                                                }
                                                className={cn(
                                                    'relative flex w-full cursor-pointer items-center rounded-sm px-2 py-1.5 text-left text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                                                    itemValue === value
                                                        ? 'bg-accent text-accent-foreground'
                                                        : '',
                                                )}
                                                onClick={() => selectItem(item)}
                                            >
                                                <Check
                                                    className={cn(
                                                        'mr-2 h-4 w-4',
                                                        value === itemValue
                                                            ? 'opacity-100'
                                                            : 'opacity-0',
                                                    )}
                                                />
                                                {itemLabel}
                                            </button>
                                        </li>
                                    );
                                })}
                        </ul>
                    </ScrollArea>
                </div>
            </PopoverContent>
        </Popover>
    );
}
AsyncSelect.displayName = 'AsyncSelect';
