import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { format, isValid, parse } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { RequiredLabel } from './RequiredLabel';
import { useVehicleLoanPageText } from './useVehicleLoanPageText';

interface VehicleLoanDateTimeFieldsProps {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
}

const TIME_PATTERN = /^([01]\d|2[0-3]):([0-5]\d)$/;

function toParts(value: string): { date: Date | undefined; time: string } {
    if (!value) {
        return { date: undefined, time: '' };
    }

    const [datePart = '', timePart = ''] = value.split('T');
    const parsed = parse(datePart, 'yyyy-MM-dd', new Date());

    return {
        date: isValid(parsed) ? parsed : undefined,
        time: timePart.slice(0, 5),
    };
}

function toValue(date: Date | undefined, time: string): string | null {
    if (!date || !TIME_PATTERN.test(time)) {
        return null;
    }

    return `${format(date, 'yyyy-MM-dd')}T${time}`;
}

export function VehicleLoanDateTimeFields({
    id,
    label,
    value,
    onChange,
    required = false,
}: Readonly<VehicleLoanDateTimeFieldsProps>) {
    const tPage = useVehicleLoanPageText();
    const initial = toParts(value);
    const [date, setDate] = useState<Date | undefined>(initial.date);
    const [time, setTime] = useState(initial.time);
    const [timeTouched, setTimeTouched] = useState(false);
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (!value) {
            setDate(undefined);
            setTime('');
            setTimeTouched(false);
        }
    }, [value]);

    function emit(nextDate: Date | undefined, nextTime: string) {
        const next = toValue(nextDate, nextTime);

        if (next) {
            onChange(next);
        }
    }

    const timeInvalid = timeTouched && time !== '' && !TIME_PATTERN.test(time);

    return (
        <div className="grid gap-2">
            <RequiredLabel htmlFor={`${id}_date`} required={required}>
                {label}
            </RequiredLabel>
            <div className="grid min-w-0 grid-cols-[minmax(9.5rem,1fr)_minmax(5.5rem,6rem)] gap-2">
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            id={`${id}_date`}
                            type="button"
                            variant="outline"
                            className={cn(
                                'w-full min-w-0 justify-start pl-3 text-left font-normal',
                                !date && 'text-muted-foreground',
                            )}
                        >
                            <span className="truncate">
                                {date
                                    ? formatDateByRegionalSettings(date)
                                    : tPage(
                                          'date_placeholder',
                                          'Pilih tanggal',
                                      )}
                            </span>
                            <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                            mode="single"
                            selected={date}
                            onSelect={(next) => {
                                setDate(next);
                                setOpen(false);
                                emit(next, time);
                            }}
                            autoFocus
                        />
                    </PopoverContent>
                </Popover>
                <Input
                    id={`${id}_time`}
                    className="min-w-0"
                    inputMode="numeric"
                    placeholder="HH:mm"
                    maxLength={5}
                    aria-invalid={timeInvalid || undefined}
                    value={time}
                    onChange={(event) => {
                        const next = event.target.value;
                        setTime(next);
                        setTimeTouched(true);
                        emit(date, next);
                    }}
                />
            </div>
            {timeInvalid && (
                <p className="text-xs text-destructive">
                    {tPage('time_invalid', 'Gunakan format 24 jam HH:mm.')}
                </p>
            )}
        </div>
    );
}
