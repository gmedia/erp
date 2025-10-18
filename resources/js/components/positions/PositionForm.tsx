'use client';

import * as React from 'react';
import { useFormContext } from 'react-hook-form';
import * as z from 'zod';

import { FormMessage } from '@/components/ui/form';
import NameField from '@/components/common/NameField';
import EntityForm from '@/components/common/EntityForm';
import { Position, PositionFormData } from '@/types/position';

const formSchema = z.object({
  name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
});

export function PositionForm({
  open,
  onOpenChange,
  position,
  onSubmit,
  isLoading = false,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  position?: Position | null;
  onSubmit: (data: PositionFormData) => void;
  isLoading?: boolean;
}) {
  const defaultValues = position ? { name: position.name } : undefined;

  const { control } = useFormContext();

  return (
    <EntityForm
      open={open}
      onOpenChange={onOpenChange}
      title={position ? 'Edit Position' : 'Add New Position'}
      onSubmit={onSubmit}
      defaultValues={defaultValues}
      schema={formSchema}
      isLoading={isLoading}
    >
      <NameField
        name="name"
        label="Name"
        placeholder="e.g., Manager"
      >
        <FormMessage />
      </NameField>
    </EntityForm>
  );
}
