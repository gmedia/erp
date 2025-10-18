'use client';

import * as React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';

import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';

interface EntityFormProps<T> {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  onSubmit: (values: T) => Promise<void> | void;
  defaultValues?: Partial<T>;
  schema?: z.ZodSchema<any>;
  children: React.ReactNode;
  isLoading?: boolean;
}

/**
 * EntityForm – a reusable dialog‑form wrapper.
 *
 * It renders a Dialog containing a Form (react‑hook‑form) and places any
 * field JSX passed as children inside the form. Validation is optional via a
 * Zod schema.
 */
export default function EntityForm<T>({
  open,
  onOpenChange,
  title,
  onSubmit,
  defaultValues,
  schema,
  children,
  isLoading = false,
}: EntityFormProps<T>) {
  const form = useForm<any>({
    resolver: schema ? zodResolver(schema as any) : undefined,
    defaultValues: defaultValues as any,
  });

  React.useEffect(() => {
    if (defaultValues) {
      form.reset(defaultValues as any);
    } else {
      form.reset({});
    }
  }, [defaultValues, form]);

  const handleSubmit = (values: any) => {
    onSubmit(values);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {/* Optional description can be added as a child of EntityForm if needed */}
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-4">
            {children}
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
                {isLoading ? 'Saving...' : 'Submit'}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
