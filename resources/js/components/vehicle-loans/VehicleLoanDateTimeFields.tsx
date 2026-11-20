import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useEffect, useState } from 'react';

interface VehicleLoanDateTimeFieldsProps {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
}

function toDisplay(value: string): { date: string; time: string } {
    if (!value) {
        return { date: '', time: '' };
    }

    const [datePart = '', timePart = ''] = value.split('T');
    const [year = '', month = '', day = ''] = datePart.split('-');

    return {
        date: year && month && day ? `${day}/${month}/${year}` : '',
        time: timePart.slice(0, 5),
    };
}

function toValue(date: string, time: string): string | null {
    const dateMatch = date.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    const timeMatch = time.match(/^(\d{2}):(\d{2})$/);

    if (!dateMatch || !timeMatch) {
        return null;
    }

    const [, day, month, year] = dateMatch;

    return `${year}-${month}-${day}T${timeMatch[1]}:${timeMatch[2]}`;
}

export function VehicleLoanDateTimeFields({
    id,
    label,
    value,
    onChange,
    required = false,
}: Readonly<VehicleLoanDateTimeFieldsProps>) {
    const initial = toDisplay(value);
    const [date, setDate] = useState(initial.date);
    const [time, setTime] = useState(initial.time);

    useEffect(() => {
        if (!value) {
            setDate('');
            setTime('');
        }
    }, [value]);

    function emit(nextDate: string, nextTime: string) {
        const next = toValue(nextDate, nextTime);

        if (next) {
            onChange(next);
        }
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor={`${id}_date`}>
                {label}
                {required && (
                    <span className="ml-1 text-destructive" aria-hidden="true">
                        *
                    </span>
                )}
            </Label>
            <div className="grid grid-cols-[1fr_110px] gap-2">
                <Input
                    id={`${id}_date`}
                    inputMode="numeric"
                    placeholder="dd/mm/yyyy"
                    value={date}
                    onChange={(event) => {
                        setDate(event.target.value);
                        emit(event.target.value, time);
                    }}
                />
                <Input
                    id={`${id}_time`}
                    type="time"
                    value={time}
                    onChange={(event) => {
                        setTime(event.target.value);
                        emit(date, event.target.value);
                    }}
                />
            </div>
        </div>
    );
}
