'use client';

import { createSimpleEntityColumns } from '@/utils/columns';
import { Position } from '@/types/position';
import { ColumnDef } from '@tanstack/react-table';

export const positionColumns: ColumnDef<Position>[] = createSimpleEntityColumns<Position>();
