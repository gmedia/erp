import axios from 'axios';
import { Check, ChevronsUpDown, Loader2, Search } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useDebounce } from '@/hooks/useDebounce';
import { cn } from '@/lib/utils';

export interface AsyncSelectProps {
    value?: string;
    /** Handler for value changes. Optional when used in filter descriptors (injected by FilterModal). */
    onValueChange?: (value: string) => void;
    url: string;
    placeholder?: string;
    className?: string;
    labelFn?: (item: any) => string; // To extract label from item
    valueFn?: (item: any) => string; // To extract value from item
}

export function AsyncSelect({
    value,
    onValueChange,
    url,
    placeholder = 'Select...',
    className,
    labelFn = (item) => item.name,
    valueFn = (item) => item.id.toString(),
}: AsyncSelectProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');
    const [items, setItems] = React.useState<any[]>([]);
    const [loading, setLoading] = React.useState(false);
    const [selectedLabel, setSelectedLabel] = React.useState<string>('');
    const [initialLoadDone, setInitialLoadDone] = React.useState(false);

    const debouncedSearch = useDebounce(search, 300);

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
                setItems(Array.isArray(data) ? data : []);
            } catch (error) {
                console.error('Failed to fetch items', error);
                setItems([]);
            } finally {
                setLoading(false);
            }
        },
        [url],
    );

    // Initial fetch to get label if value is provided
    React.useEffect(() => {
        const fetchInitialLabel = async () => {
            if (value && value !== 'null' && value !== 'undefined' && !selectedLabel && !initialLoadDone) {
                try {
                    // Try to fetch specific item by ID
                    const [baseUrl] = url.split('?');
                    const response = await axios.get(`${baseUrl}/${value}`);
                    const data = response.data.data || response.data;
                    if (data) {
                        setSelectedLabel(labelFn(data));
                    }
                } catch (e) {
                    // If fetch by ID fails, fallback to list fetch?
                    // Or maybe the value is invalid.
                } finally {
                    setInitialLoadDone(true);
                }
            }
        };

        fetchInitialLabel();
    }, [value, url, selectedLabel, initialLoadDone, labelFn]);

    // Fetch items on open or search
    React.useEffect(() => {
        if (open) {
            fetchItems(debouncedSearch);
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
        if (!value || value === 'null' || value === 'undefined') setSelectedLabel('');
    }, [value]);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'w-full justify-between font-normal',
                        !value && 'text-muted-foreground',
                        className,
                    )}
                >
                    {selectedLabel || placeholder}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
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
                    <div
                        className="max-h-[200px] overflow-y-auto p-1"
                        role="listbox"
                    >
                        {loading && (
                            <div className="flex items-center justify-center p-4">
                                <Loader2 className="h-4 w-4 animate-spin" />
                            </div>
                        )}
                        {!loading && items.length === 0 && (
                            <div className="py-6 text-center text-sm text-muted-foreground">
                                No results found.
                            </div>
                        )}
                        {!loading &&
                            items.map((item) => {
                                const itemValue = valueFn(item);
                                const itemLabel = labelFn(item);
                                return (
                                    <div
                                        key={itemValue}
                                        role="option"
                                        aria-selected={itemValue === value}
                                        className={cn(
                                            'relative flex cursor-pointer items-center rounded-sm px-2 py-1.5 text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                                            itemValue === value
                                                ? 'bg-accent text-accent-foreground'
                                                : '',
                                        )}
                                        onClick={() => {
                                            onValueChange?.(itemValue);
                                            setSelectedLabel(itemLabel);
                                            setOpen(false);
                                        }}
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
                                    </div>
                                );
                            })}
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
AsyncSelect.displayName = 'AsyncSelect';
