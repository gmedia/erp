'use client';

import { createSimpleEntityColumns } from '@/utils/columns';
import { Department } from '@/types/department';
import { ColumnDef } from '@tanstack/react-table';

export const departmentColumns: ColumnDef<Department>[] = createSimpleEntityColumns<Department>();
