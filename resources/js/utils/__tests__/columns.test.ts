// Type checking test for column utilities
// This file performs compile-time type assertions — no runtime test framework needed.
// Run: tsc --noEmit (ensures types compile cleanly)

import type { ColumnDef } from '@tanstack/react-table';
import { Department } from '@/types/department';
import { Employee } from '@/types/employee';
import { Position } from '@/types/position';
import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createTextColumn,
} from '../columns';

// ── Helper ──

type AccessorKeyCol = ColumnDef<unknown> & { accessorKey: string };

function accessorKeyOf(col: AccessorKeyCol): string {
    return col.accessorKey;
}

type WithAccessorKey<T> = Extract<ColumnDef<T>, { accessorKey: string }>;
type WithId<T> = Extract<ColumnDef<T>, { id: string }>;
type WithEnableSorting<T> = Extract<ColumnDef<T>, { enableSorting: unknown }>;

// ── createSelectColumn ──

const _selectCol = createSelectColumn<Department>() as WithId<Department>;
const _selectIdCheck = _selectCol.id as string satisfies string;

type _SelectHasMeta = Extract<ColumnDef<Department>, { meta: unknown }> extends { meta: unknown } ? true : false;
const _selectMetaCheck: _SelectHasMeta = true;

// ── createTextColumn ──

const _textCol = createTextColumn<Department>({ accessorKey: 'name', label: 'Name' }) as WithAccessorKey<Department>;
type _TextAccessorKey = typeof _textCol['accessorKey'];
const _textAk: _TextAccessorKey = 'name';
type _TextHeader = Extract<ColumnDef<Department>, { header: unknown }>['header'];
const _textHeader: _TextHeader = 'Name';

// ── createDateColumn ──

const _dateCol = createDateColumn<Department>({ accessorKey: 'created_at', label: 'Created At' }) as WithAccessorKey<Department>;
type _DateAccessorKey = typeof _dateCol['accessorKey'];
const _dateAk: _DateAccessorKey = 'created_at';

const _dateColNoSort = createDateColumn<Position>({
    accessorKey: 'updated_at',
    label: 'Updated At',
    enableSorting: false,
}) as WithEnableSorting<Position>;
type _DateNoSort = typeof _dateColNoSort extends { enableSorting: false } ? true : false;
const _dateNoSortCheck: _DateNoSort = true;

// ── createEmailColumn ──

const _emailCol = createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }) as WithAccessorKey<Employee>;
type _EmailAccessorKey = typeof _emailCol['accessorKey'];
const _emailAk: _EmailAccessorKey = 'email';

// ── createPhoneColumn ──

const _phoneCol = createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }) as WithAccessorKey<Employee>;
type _PhoneAccessorKey = typeof _phoneCol['accessorKey'];
const _phoneAk: _PhoneAccessorKey = 'phone';

// ── createCurrencyColumn ──

const _currencyCol = createCurrencyColumn<Employee>({ accessorKey: 'salary', label: 'Salary' }) as WithAccessorKey<Employee>;
type _CurrencyAccessorKey = typeof _currencyCol['accessorKey'];
const _currencyAk: _CurrencyAccessorKey = 'salary';

// ── createActionsColumn ──

const _actionsCol = createActionsColumn<Department>() as WithId<Department>;
const _actionsIdCheck = _actionsCol.id as string satisfies string;

const _actionsColWithCb = createActionsColumn<Department>({
    onEdit: () => {},
    onDelete: () => {},
}) as WithId<Department>;
const _actionsColWithCbIdCheck = _actionsColWithCb.id as string satisfies string;

// ── Column array builds ──

const _deptColumns = [
    createSelectColumn<Department>(),
    createTextColumn<Department>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Department>({ accessorKey: 'created_at', label: 'Created At' }),
    createActionsColumn<Department>({ onEdit: () => {}, onDelete: () => {} }),
] as const;
type _DeptColsLen = typeof _deptColumns['length'] extends 4 ? true : false;
const _deptLenCheck: _DeptColsLen = true;

const _empColumns = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    createCurrencyColumn<Employee>({ accessorKey: 'salary', label: 'Salary' }),
    createDateColumn<Employee>({ accessorKey: 'hire_date', label: 'Hire Date' }),
    createActionsColumn<Employee>({ onEdit: () => {}, onDelete: () => {}, onView: () => {} }),
] as const;
type _EmpColsLen = typeof _empColumns['length'] extends 7 ? true : false;
const _empLenCheck: _EmpColsLen = true;

const _posColumns = [
    createSelectColumn<Position>(),
    createTextColumn<Position>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Position>({ accessorKey: 'updated_at', label: 'Updated At', enableSorting: false }),
    createActionsColumn<Position>({ onEdit: () => {}, onDelete: () => {} }),
] as const;
type _PosColsLen = typeof _posColumns['length'] extends 4 ? true : false;
const _posLenCheck: _PosColsLen = true;

// ── Exhaustiveness guard ──
void _selectIdCheck;
void _selectMetaCheck;
void _textAk;
void _textHeader;
void _dateAk;
void _dateNoSortCheck;
void _emailAk;
void _phoneAk;
void _currencyAk;
void _actionsIdCheck;
void _actionsColWithCbIdCheck;
void _deptLenCheck;
void _empLenCheck;
void _posLenCheck;
void accessorKeyOf;
void _selectCol;
void _textCol;
void _dateCol;
void _dateColNoSort;
void _emailCol;
void _phoneCol;
void _currencyCol;
void _actionsCol;
void _actionsColWithCb;
void _deptColumns;
void _empColumns;
void _posColumns;
