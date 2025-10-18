'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import * as React from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';
import { FormMessage } from '@/components/ui/form';
import NameField from '@/components/common/NameField';
import EntityForm from '@/components/common/EntityForm';
import { DepartmentFormData } from '@/types/department';

const formSchema = z.object({
  name: z
    .string()
    .min(2, { message: 'Name must be at least 2 characters.' })
    .max(255, { message: 'Maximum 255 characters.' }),
});

export function DepartmentForm({
  open,
  onOpenChange,
  department,
  onSubmit,
  isLoading = false,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  department?: DepartmentFormData | null;
  onSubmit: (data: DepartmentFormData) => void;
  isLoading?: boolean;
}) {
  const defaultValues = department ? { name: department.name } : undefined;

  const form = useForm({
    resolver: zodResolver(formSchema),
    defaultValues: defaultValues as any,
  });
  const { control } = form;

  return (
    <EntityForm form={form}
      open={open}
      onOpenChange={onOpenChange}
      title={department ? 'Edit Department' : 'Add New Department'}
      onSubmit={onSubmit}
      defaultValues={defaultValues}
      schema={formSchema}
      isLoading={isLoading}
    >
      <NameField
        name="name"
        label="Name"
        placeholder="e.g., Marketing"
      >
        <FormMessage />
      </NameField>
      {/* Footer is handled by EntityForm */}
    </EntityForm>
  );
}
