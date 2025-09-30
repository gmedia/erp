'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import * as React from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Form,
    FormControl,
    FormDescription,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { Employee } from '@/types';

const formSchema = z.object({
    name: z.string().min(2, {
        message: 'Name must be at least 2 characters.',
    }),
    email: z.string().email({
        message: 'Please enter a valid email address.',
    }),
    phone: z.string().min(10, {
        message: 'Phone number must be at least 10 digits.',
    }),
    department: z.string().min(2, {
        message: 'Department must be at least 2 characters.',
    }),
    position: z.string().min(2, {
        message: 'Position must be at least 2 characters.',
    }),
    salary: z.string().regex(/^\d+(\.\d{1,2})?$/, {
        message: 'Please enter a valid salary amount.',
    }),
    hire_date: z.date({
        message: 'Hire date is required.',
    }),
});

interface EmployeeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    employee?: Employee | null;
    onSubmit: (data: z.infer<typeof formSchema>) => void;
    isLoading?: boolean;
}

const DEPARTMENTS = [
    'Engineering',
    'Sales',
    'Marketing',
    'Human Resources',
    'Finance',
    'Operations',
    'Customer Support',
    'Product',
    'Design',
    'Legal',
];

const POSITIONS = [
  'Junior',
  'Mid-level',
  'Senior',
  'Senior Developer',
  'Lead',
  'Manager',
  'Director',
  'VP',
  'C-level',
];

export function EmployeeForm({
    open,
    onOpenChange,
    employee,
    onSubmit,
    isLoading = false,
}: EmployeeFormProps) {
    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            name: employee?.name || '',
            email: employee?.email || '',
            phone: employee?.phone || '',
            department: employee?.department || '',
            position: employee?.position || '',
            salary: employee?.salary || '',
            hire_date: employee?.hire_date
                ? new Date(employee.hire_date)
                : new Date(),
        },
    });

    React.useEffect(() => {
        if (employee) {
            form.reset({
                name: employee.name,
                email: employee.email,
                phone: employee.phone,
                department: employee.department,
                position: employee.position,
                salary: employee.salary,
                hire_date: new Date(employee.hire_date),
            });
        } else {
            form.reset({
                name: '',
                email: '',
                phone: '',
                department: '',
                position: '',
                salary: '',
                hire_date: new Date(),
            });
        }
    }, [employee, form]);

    const handleSubmit = (values: z.infer<typeof formSchema>) => {
        onSubmit(values);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>
                        {employee ? 'Edit Employee' : 'Add New Employee'}
                    </DialogTitle>
                    <DialogDescription>
                        {employee
                            ? "Update the employee's information below."
                            : 'Fill out the form below to add a new employee.'}
                    </DialogDescription>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(handleSubmit)}
                        className="space-y-4"
                    >
                        <FormField
                            control={form.control}
                            name="name"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Name</FormLabel>
                                    <FormControl>
                                        <Input
                                            placeholder="John Doe"
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
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
                                        <Input
                                            placeholder="+1 (555) 123-4567"
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
                        <FormField
                            control={form.control}
                            name="department"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Department</FormLabel>
                                    <Select
                                        onValueChange={field.onChange}
                                        defaultValue={field.value}
                                    >
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a department" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            {DEPARTMENTS.map((dept) => (
                                                <SelectItem
                                                    key={dept}
                                                    value={dept}
                                                >
                                                    {dept}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
                        <FormField
                            control={form.control}
                            name="position"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Position</FormLabel>
                                    <Select
                                        onValueChange={field.onChange}
                                        defaultValue={field.value}
                                    >
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a position" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            {POSITIONS.map((pos) => (
                                                <SelectItem
                                                    key={pos}
                                                    value={pos}
                                                >
                                                    {pos}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
                        <FormField
                            control={form.control}
                            name="salary"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Salary</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            placeholder="50000.00"
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormDescription>
                                        Enter salary as a decimal number (e.g.,
                                        50000.00)
                                    </FormDescription>
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
                                                        format(
                                                            field.value,
                                                            'PPP',
                                                        )
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
                                                disabled={(date) =>
                                                    date > new Date() ||
                                                    date <
                                                        new Date('1900-01-01')
                                                }
                                                initialFocus
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                                disabled={isLoading}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isLoading}>
                                {isLoading
                                    ? 'Saving...'
                                    : employee
                                      ? 'Update'
                                      : 'Add'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
