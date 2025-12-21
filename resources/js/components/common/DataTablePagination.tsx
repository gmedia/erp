'use client';

import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface DataTablePaginationProps {
    pagination: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
    onPageChange: (page: number) => void;
    onPageSizeChange: (per_page: number) => void;
}

export function DataTablePagination({
    pagination,
    onPageChange,
    onPageSizeChange,
}: DataTablePaginationProps) {
    const renderPageNumbers = () => {
        const pages = [];
        const maxPages = 5;
        const startPage = Math.max(1, pagination.page - Math.floor(maxPages / 2));
        const endPage = Math.min(pagination.last_page, startPage + maxPages - 1);

        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <PaginationItem key={i}>
                    <PaginationLink
                        href="#"
                        isActive={i === pagination.page}
                        onClick={(e) => {
                            e.preventDefault();
                            onPageChange(i);
                        }}
                    >
                        {i}
                    </PaginationLink>
                </PaginationItem>
            );
        }
        return pages;
    };

    const handlePageSizeChange = (per_page: string) => {
        onPageSizeChange(Number(per_page));
    };

    return (
        <div className="flex items-center justify-between py-4 text-sm text-muted-foreground">
            <div className="flex items-center space-x-2">
                <p>Rows per page</p>
                <Select
                    value={String(pagination.per_page)}
                    onValueChange={handlePageSizeChange}
                >
                    <SelectTrigger className="w-[70px] border-border bg-background">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="border-border bg-background text-foreground">
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="15">15</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
                <p>
                    Showing {pagination.from} to {pagination.to} of{' '}
                    {pagination.total} entries
                </p>
            </div>

            <Pagination>
                <PaginationContent>
                    <PaginationItem>
                        <PaginationPrevious
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                if (pagination.page > 1) {
                                    onPageChange(pagination.page - 1);
                                }
                            }}
                            aria-disabled={pagination.page <= 1}
                            className={
                                pagination.page <= 1
                                    ? 'pointer-events-none opacity-50'
                                    : ''
                            }
                        />
                    </PaginationItem>

                    {renderPageNumbers()}

                    <PaginationItem>
                        <PaginationNext
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                if (pagination.page < pagination.last_page) {
                                    onPageChange(pagination.page + 1);
                                }
                            }}
                            aria-disabled={pagination.page >= pagination.last_page}
                            className={
                                pagination.page >= pagination.last_page
                                    ? 'pointer-events-none opacity-50'
                                    : ''
                            }
                        />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>
        </div>
    );
}
