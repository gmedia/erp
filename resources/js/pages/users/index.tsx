'use client';

import { DataTablePagination } from '@/components/common/DataTablePagination';
import {
    buildStandalonePaginationView,
    StandaloneTablePage,
    TableEmptyStateRow,
    TableLoadingRow,
} from '@/components/common/StandaloneTablePage';
import { EmployeeSelector } from '@/components/permissions/EmployeeSelector';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { UserForm } from '@/components/users/UserForm';
import { useTranslation } from '@/contexts/i18n-context';
import { usePermissions } from '@/hooks/usePermissions';
import { useEmployeeUser } from '@/hooks/users/useEmployeeUser';
import axiosInstance from '@/lib/axios';
import type { BreadcrumbItem } from '@/types';
import { Loader2, Pencil, Plus, Search, Users } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';

interface UserFormData {
    employee_id: string;
    name: string;
    email: string;
    password: string;
}

interface UserRow {
    id: number;
    name: string;
    email: string;
    employee: { id: number; name: string } | null;
}

interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
];

const EMPTY_FORM: UserFormData = {
    employee_id: '',
    name: '',
    email: '',
    password: '',
};

function useUsersManager() {
    const [rows, setRows] = useState<UserRow[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>({
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
    });
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [lockedEmployee, setLockedEmployee] = useState(false);

    const form = useForm<UserFormData>({ defaultValues: EMPTY_FORM });
    const { loading, userExists, errors, fetchUser, saveUser } =
        useEmployeeUser(form);
    const selectedEmployeeId = form.watch('employee_id');

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebouncedSearch(search.trim());
            setMeta((prev) => ({ ...prev, current_page: 1 }));
        }, 300);

        return () => window.clearTimeout(timer);
    }, [search]);

    const loadUsers = useCallback(async () => {
        setIsLoading(true);
        try {
            const response = await axiosInstance.get('/api/users/table', {
                params: {
                    search: debouncedSearch || undefined,
                    page: meta.current_page,
                    per_page: meta.per_page,
                },
            });
            setRows(response.data.data);
            setMeta((prev) => ({ ...prev, ...response.data.meta }));
        } catch (error) {
            console.error('Failed to load users', error);
        } finally {
            setIsLoading(false);
        }
    }, [debouncedSearch, meta.current_page, meta.per_page]);

    useEffect(() => {
        void loadUsers();
    }, [loadUsers]);

    useEffect(() => {
        if (dialogOpen && selectedEmployeeId) {
            void fetchUser(selectedEmployeeId);
        }
    }, [dialogOpen, selectedEmployeeId, fetchUser]);

    const openCreate = () => {
        form.reset(EMPTY_FORM);
        setLockedEmployee(false);
        setDialogOpen(true);
    };

    const openEdit = (row: UserRow) => {
        if (!row.employee) return;
        form.reset({ ...EMPTY_FORM, employee_id: String(row.employee.id) });
        setLockedEmployee(true);
        setDialogOpen(true);
    };

    const handleDialogChange = (open: boolean) => {
        setDialogOpen(open);
        if (!open) {
            form.reset(EMPTY_FORM);
            setLockedEmployee(false);
        }
    };

    const handleSave = async () => {
        if (!selectedEmployeeId) return;
        await saveUser(selectedEmployeeId, form.getValues());
        void loadUsers();
    };

    return {
        form,
        rows,
        meta,
        setMeta,
        search,
        setSearch,
        isLoading,
        dialogOpen,
        lockedEmployee,
        selectedEmployeeId,
        loading,
        userExists,
        errors,
        openCreate,
        openEdit,
        handleDialogChange,
        handleSave,
    };
}

type UsersManager = ReturnType<typeof useUsersManager>;

export default function UsersIndex() {
    const manager = useUsersManager();
    const { can } = usePermissions();
    const canEdit = can('user.edit');

    return (
        <StandaloneTablePage
            title="Users"
            breadcrumbs={breadcrumbs}
            heading="Users"
            description={<UsersDescription />}
            actions={
                canEdit ? <UsersAddButton onAdd={manager.openCreate} /> : null
            }
        >
            <UsersBody manager={manager} canEdit={canEdit} />
        </StandaloneTablePage>
    );
}

// useTranslation requires AppI18nProvider, which wraps AppLayout. Keep every
// translated piece as a child component so it renders inside that provider.
function UsersDescription() {
    const { t } = useTranslation();

    return <>{t('users.description')}</>;
}

function UsersAddButton({ onAdd }: Readonly<{ onAdd: () => void }>) {
    const { t } = useTranslation();

    return (
        <Button onClick={onAdd}>
            <Plus className="mr-2 h-4 w-4" />
            {t('users.add')}
        </Button>
    );
}

function UsersBody({
    manager,
    canEdit,
}: Readonly<{ manager: UsersManager; canEdit: boolean }>) {
    const { t } = useTranslation();
    const {
        form,
        rows,
        meta,
        setMeta,
        search,
        setSearch,
        isLoading,
        dialogOpen,
        lockedEmployee,
        selectedEmployeeId,
        loading,
        userExists,
        errors,
        openEdit,
        handleDialogChange,
        handleSave,
    } = manager;

    const paginationView = buildStandalonePaginationView(meta, rows.length);

    let tableBody: React.ReactNode;
    if (isLoading && rows.length === 0) {
        tableBody = (
            <TableLoadingRow
                colSpan={5}
                icon={<Loader2 className="h-8 w-8 animate-spin text-primary" />}
                message={t('users.loading')}
            />
        );
    } else if (rows.length === 0) {
        tableBody = (
            <TableEmptyStateRow
                colSpan={5}
                icon={<Users className="h-10 w-10 text-muted-foreground" />}
                title={t('users.empty_title')}
                description={t('users.empty_description')}
            />
        );
    } else {
        tableBody = rows.map((row) => (
            <TableRow key={row.id} data-testid="user-row">
                <TableCell className="font-medium">
                    {row.name}
                    {canEdit && !row.employee && (
                        <span className="mt-1 block text-xs font-normal text-muted-foreground md:hidden">
                            {t('users.edit_requires_employee')}
                        </span>
                    )}
                </TableCell>
                <TableCell
                    className="max-w-[160px] break-all md:max-w-[220px]"
                    title={row.email}
                >
                    {row.email}
                </TableCell>
                <TableCell>
                    {row.employee ? (
                        row.employee.name
                    ) : (
                        <span className="text-muted-foreground">
                            {t('users.no_employee')}
                        </span>
                    )}
                </TableCell>
                <TableCell>
                    <Badge variant={row.employee ? 'success' : 'outline'}>
                        {row.employee
                            ? t('users.status_linked')
                            : t('users.status_unlinked')}
                    </Badge>
                </TableCell>
                <TableCell className="bg-background text-right md:sticky md:right-0 md:z-20">
                    {canEdit && (
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    {/* Wrapper span: tombol disabled tidak memicu hover tooltip. */}
                                    <span className="inline-flex">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            disabled={!row.employee}
                                            onClick={() => openEdit(row)}
                                            aria-label={t('users.edit_user')}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Button>
                                    </span>
                                </TooltipTrigger>
                                {!row.employee && (
                                    <TooltipContent>
                                        {t('users.edit_requires_employee')}
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        </TooltipProvider>
                    )}
                </TableCell>
            </TableRow>
        ));
    }

    return (
        <>
            <div className="relative w-full max-w-sm">
                <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder={t('users.search_placeholder')}
                    aria-label={t('users.search_placeholder')}
                    className="pl-8"
                />
            </div>

            <Table>
                <TableHeader className="[&_th]:text-muted-foreground">
                    <TableRow>
                        <TableHead>{t('users.columns.name')}</TableHead>
                        <TableHead>{t('users.columns.email')}</TableHead>
                        <TableHead>{t('users.columns.employee')}</TableHead>
                        <TableHead>{t('users.columns.status')}</TableHead>
                        <TableHead className="w-[60px] bg-background text-right md:sticky md:right-0 md:z-20">
                            <span className="sr-only">
                                {t('common.actions')}
                            </span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>{tableBody}</TableBody>
            </Table>

            <DataTablePagination
                pagination={paginationView}
                onPageChange={(page) =>
                    setMeta((prev) => ({ ...prev, current_page: page }))
                }
                onPageSizeChange={(perPage) =>
                    setMeta((prev) => ({
                        ...prev,
                        per_page: perPage,
                        current_page: 1,
                    }))
                }
            />

            <Dialog open={dialogOpen} onOpenChange={handleDialogChange}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {lockedEmployee
                                ? t('users.edit_user')
                                : t('users.add')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('users.dialog_description')}
                        </DialogDescription>
                    </DialogHeader>

                    <FormProvider {...form}>
                        {!lockedEmployee && (
                            <div className="max-w-md">
                                <EmployeeSelector />
                            </div>
                        )}
                        {selectedEmployeeId && (
                            <UserForm
                                loading={loading}
                                userExists={userExists}
                                errors={errors}
                                onSave={() => void handleSave()}
                                canEdit={canEdit}
                            />
                        )}
                    </FormProvider>
                </DialogContent>
            </Dialog>
        </>
    );
}
