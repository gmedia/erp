'use client';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { DEPARTMENTS, POSITIONS } from '@/constants';
import { cn } from '@/lib/utils';
import { Employee } from '@/types';
import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import * as React from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

const formSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    email: z.string().email({ message: 'Please enter a valid email address.' }),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' }),
    department: z
        .string()
        .min(2, { message: 'Department must be at least 2 characters.' }),
    position: z
        .string()
        .min(2, { message: 'Position must be at least 2 characters.' }),
    salary: z
        .string()
        .regex(/^\d+$/, { message: 'Please enter a valid salary amount.' }),
    hire_date: z.date({ message: 'Hire date is required.' }),
});

export function EmployeeForm({
    open,
    onOpenChange,
    employee,
    onSubmit,
    isLoading = false,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    employee?: Employee | null;
    onSubmit: (data: z.infer<typeof formSchema>) => void;
    isLoading?: boolean;
}) {
    const defaultValues = employee
        ? {
              name: employee.name,
              email: employee.email,
              phone: employee.phone,
              department: employee.department,
              position: employee.position,
              salary: employee.salary,
              hire_date: new Date(employee.hire_date),
          }
        : {
              // Provide a default hire date for new employees to satisfy validation
              hire_date: new Date(),
          };

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: defaultValues as any,
    });
    const { _control, reset } = form;

    // Reset form values when the selected employee changes (edit mode)
    React.useEffect(() => {
        if (employee) {
            reset({
                name: employee.name,
                email: employee.email,
                phone: employee.phone,
                department: employee.department,
                position: employee.position,
                salary: employee.salary,
                hire_date: new Date(employee.hire_date),
            });
        } else {
            reset({});
        }
    }, [employee, reset]);

    return (
        <EntityForm
            open={open}
            onOpenChange={onOpenChange}
            title={employee ? 'Edit Employee' : 'Add New Employee'}
            onSubmit={onSubmit}
            defaultValues={defaultValues}
            schema={formSchema}
            isLoading={isLoading}
            form={form}
        >
            <NameField name="name" label="Name" placeholder="John Doe">
                <FormMessage />
            </NameField>
            <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                    <FormItem>
                        <FormLabel>Email</FormLabel>
                        <FormControl>
                            <Input
                                type="email"
                                placeholder="john.doe@example.com"
                                {...field}
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                )}
            />
            <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                    <FormItem>
                        <FormLabel>Phone</FormLabel>
                        <FormControl>
                            <Input placeholder="+1 (555) 123-4567" {...field} />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                )}
            />
            <SelectField
                name="department"
                label="Department"
                options={DEPARTMENTS}
                placeholder="Select a department"
            >
                <FormMessage />
            </SelectField>
            <SelectField
                name="position"
                label="Position"
                options={POSITIONS}
                placeholder="Select a position"
            >
                <FormMessage />
            </SelectField>
            <FormField
                control={form.control}
                name="salary"
                render={({ field }) => (
                    <FormItem>
                        <FormLabel>Salary</FormLabel>
                        <FormControl>
                            <Input
                                type="number"
                                placeholder="50000"
                                {...field}
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                )}
            />
            <FormField
                control={form.control}
                name="hire_date"
                render={({ field }) => (
                    <FormItem className="flex flex-col">
                        <FormLabel>Hire Date</FormLabel>
                        <Popover>
                            <PopoverTrigger asChild>
                                <FormControl>
                                    <Button
                                        variant={'outline'}
                                        className={cn(
                                            'w-full pl-3 text-left font-normal',
                                            !field.value &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {field.value ? (
                                            format(field.value, 'PPP')
                                        ) : (
                                            <span>Pick a date</span>
                                        )}
                                        <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                                    </Button>
                                </FormControl>
                            </PopoverTrigger>
                            <PopoverContent
                                className="w-auto p-0"
                                align="start"
                            >
                                <Calendar
                                    mode="single"
                                    selected={field.value}
                                    onSelect={field.onChange}
                                    disabled={(date: Date) =>
                                        date > new Date() ||
                                        date < new Date('1900-01-01')
                                    }
                                    initialFocus
                                />
                            </PopoverContent>
                        </Popover>
                        <FormMessage />
                    </FormItem>
                )}
            />
        </EntityForm>
    );
}
